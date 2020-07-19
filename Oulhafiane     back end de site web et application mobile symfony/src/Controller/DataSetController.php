<?php

namespace App\Controller;

use App\Entity\DataSet;
use App\Entity\SurveyToken;
use App\Entity\Part;
use App\Entity\Variable;
use App\Entity\FileExcel;
use App\Service\CurrentUser;
use App\Service\FormHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Ramsey\Uuid\Uuid;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use App\Helper\UploadedBase64EncodedFile;
use App\Helper\Base64EncodedFile;

class DataSetController extends AbstractController
{    
    private $validator;
    private $cr;
    private $imagineCacheManager;
    private $form;
    private $em;
    private $serializer;
    private $JWTencoder;

    public function __construct(ValidatorInterface $validator, CurrentUser $cr, FormHandler $form, EntityManagerInterface $em, SerializerInterface $serializer, JWTEncoderInterface $JWTencoder)
    {
        $this->validator = $validator;
        $this->cr = $cr;
        $this->form = $form;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->JWTencoder = $JWTencoder;
    }
    
    private function checkRoleAndId(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (null !== $data && array_key_exists('id', $data))
            throw $this->createAccessDeniedException();

        $this->denyAccessUnlessGranted('ROLE_SEARCHER');
        return $data;
    }

    public function import_data_xls($dataset, $db, $db_name)
    {
        $filesystem = new Filesystem();
        if ($filesystem->exists($db_name))
            $filesystem->copy($db_name, $db_name.".copy", true);
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($dataset->getFileExcel()->getFile()->getPathName());
        $countSheets = $spreadsheet->getSheetCount();
        try {
            for ($i=0; $i < $countSheets; $i++) { 
                $worksheet = $spreadsheet->getSheet($i);
                $part = new Part();
                $part->setTitle($worksheet->getTitle());
                $part->setDataset($dataset);
                $this->em->persist($part);
                $db->exec("CREATE TABLE IF NOT EXISTS data (id INTEGER PRIMARY KEY AUTOINCREMENT);");
                $in_headers = true;
                $variables = array();
                $indexRow = 0;
                foreach ($worksheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(TRUE);
                    $count = 0;
                    $sqlVariables = "";
                    $sqlValues = "";
                    foreach ($cellIterator as $cell) {
                        $value = $cell->getValue();
                        if ($in_headers === true) {
                            $variable = new Variable();
                            $variable->setName($value === NULL ? "Undefined" : $value);
                            $variable->setQuestion($value === NULL ? "Undefined" : $value);
                            $variable->setType(5);
                            $variable->setPart($part);
                            $variable->setNameInDb($variable->getName().bin2hex(random_bytes(10)));
                            $this->em->persist($variable);
                            $db->exec("ALTER TABLE data ADD `".$variable->getNameInDb()."` ".$this->getType($variable->getType()).";");
                            array_push($variables, $variable);
                        } else {
                            if ($i > 0)
                                $sqlValues .= "`".$variables[$count]->getNameInDb().'`="'.$value.'",';
                            else {
                                $sqlVariables .= "`".$variables[$count]->getNameInDb()."`,";
                                $sqlValues .= '"'.$value.'",';
                            }
                        }
                        $count++;
                    }
                    if ($in_headers === false) {
                        if ($i > 0)
                            $sql = "UPDATE data SET ".rtrim($sqlValues, ", ")." WHERE rowid=".$indexRow.";";
                        else
                            $sql = "INSERT INTO data (".rtrim($sqlVariables, ", ").") VALUES(".rtrim($sqlValues, ", ").");";
                        $db->exec($sql);
                    }
                    $in_headers = false;
                    $indexRow++;
                }
                $filesystem->remove($db_name.".copy");
           
            }
        } catch (\Exception $ex) {
            if ($filesystem->exists($db_name.".copy")) {
                $filesystem->copy($db_name.".copy", $db_name, true);
                $filesystem->remove($db_name.".copy");
            }
            throw new HttpException(400, $ex->getMessage());
        }
    }

    public function import_data_csv($dataset, $db)
    {
        echo "CSV will be available soon...\n";
    }

    public function setOwner($dataset)
    {
        $dataset->setOwner($this->cr->getCurrentUser($this));
        $dataset->setUuid(Uuid::uuid4()->toString());

        $fileExcel = $dataset->getFileExcel();

        if (null !== $fileExcel) {
            $file = new UploadedBase64EncodedFile(new Base64EncodedFile($fileExcel->getFile()));
            $fileExcel->setFile($file);
            $fileExcel->setDataset($dataset);
            $fileExcel->setLink($file->getClientOriginalName());
    
            $violations = $this->validator->validate($dataset, null, ['new-file']);
            $message = '';
            foreach ($violations as $violation) {
                $message .= $violation->getPropertyPath() . ': ' . $violation->getMessage() . ' ';
            }
            if (count($violations) !== 0)
                throw new HttpException(406, $message);
        }
        try {
            $db_name = $this->getParameter('datasets_location').'/'.$dataset->getUuid().'.db';
            $filesystem = new Filesystem();
            while ($filesystem->exists($db_name)) {
                $dataset->setUuid(Uuid::uuid4()->toString());
                $db_name = $this->getParameter('datasets_location').'/'.$dataset->getUuid().'.db';
            }
            $db=new \SQLite3($db_name);

            //Getting data from excel file is set
            if (null !== $fileExcel) {
                $extension = ltrim(strstr($fileExcel->getLink(), '.'), '.');
                if ("xlsx" === $extension || "xls" === $extension)
                    $this->import_data_xls($dataset, $db, $db_name);
                else if ("csv" === $extension)
                    $this->import_data_csv($dataset, $db, $db_name);
            }

            return $dataset->getUuid();
        } catch (\Exception $ex) {
            throw new HttpException(400, "Cannot create the dataset, Make sure all columns are defined, without collapsing or grouping, and the data have no special or complicated characters.");
        }
    }

    public function getType($variable)
    {
        switch($variable) {
            case 0:
                return "TEXT";
            case 1:
                return "INTEGER";
            case 2:
                return "REAL";
            default:
                return "TEXT";
        }
    }

    public function afterPersistVariables($part, $state)
    {
        $dataset = $part->getDataset();
        $db_name = $this->getParameter('datasets_location').'/'.$dataset->getUuid().'.db';
        $filesystem = new Filesystem();
        if (true === $state && $filesystem->exists($db_name.".copy")) {
            $filesystem->remove($db_name.".copy");
        } else if ($filesystem->exists($db_name.".copy")) {
            $filesystem->copy($db_name.".copy", $db_name, true);
            $filesystem->remove($db_name.".copy");
        }
    }

    public function addVariables($part)
    {
        $variables = $part->getVariables();
        if (null === $variables) return false;
        $dataset = $part->getDataset();
        $db_name = $this->getParameter('datasets_location').'/'.$dataset->getUuid().'.db';
        $filesystem = new Filesystem();
        if ($filesystem->exists($db_name))
            $filesystem->copy($db_name, $db_name.".copy", true);
        $db=new \SQLite3($db_name);
        $db->exec("CREATE TABLE IF NOT EXISTS data (id INTEGER PRIMARY KEY AUTOINCREMENT);");
        foreach($variables as $variable) {
            $violations = $this->validator->validate($variable, null, ['add-variables']);
            $message = '';
            foreach ($violations as $violation) {
                $message .= $violation->getPropertyPath() . ': ' . $violation->getMessage() . ' ';
            }
            if (count($violations) !== 0)
                throw new HttpException(406, $message);

            $variable->setPart($part);
            $variable->setNameInDb($variable->getName().bin2hex(random_bytes(10)));
            try {
                $this->em->persist($variable);
                $db->exec("ALTER TABLE data ADD `".$variable->getNameInDb()."` ".$this->getType($variable->getType()).";");
            } catch (\Exception $ex) {
                if ($filesystem->exists($db_name.".copy")) {
                    $filesystem->copy($db_name.".copy", $db_name, true);
                    $filesystem->remove($db_name.".copy");
                }
                throw new HttpException(400, $ex->getMessage());
            }
        }
        return 'my-dataset';
    }

    public function setDataSetToObject($object, $dataset)
    {
        $object->setDataSet($dataset);
        $this->addVariables($object);
        return true;
    }
    
    public function doNothing($object)
    {
        return false;
    }

    /**
     * @Route("/api/current/dataset/{uuid}", name="update_dataset", methods={"PATCH"}, requirements={"uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function UpdateDataSetAction(Request $request, $uuid)
    {
        $this->checkRoleAndId($request);

        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $dataset->getOwner())
            throw new HttpException(401, "You are not the owner.");

        return $this->form->update($request, DataSet::class, array($this, 'doNothing'), array($this, 'doNothing'), ['update-dataset'], ['update-dataset'], $dataset->getId());
    }

    /**
     * @Route("/api/current/dataset/{uuid}", name="specific_dataset", methods={"GET"}, requirements={"uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function SpecificDataSetAction($uuid)
    {
        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        $current = $this->cr->getCurrentUser($this);
        if ($current != $dataset->getOwner())
            throw new HttpException(401, "You are not the owner");
        $data = $this->serializer->serialize($dataset, 'json', SerializationContext::create()->setGroups(array('my-dataset', 'my-dataset')));
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/current/dataset/{uuid}/data", name="get_data_of_dataset", methods={"GET"}, requirements={"uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function getDataOfDatasetAction(Request $request, $uuid) {
        $offset = $request->query->get('offset', 0);

        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        $current = $this->cr->getCurrentUser($this);
        if ($current != $dataset->getOwner())
            throw new HttpException(401, "You are not the owner");
        $variables = $this->em->getRepository(Variable::class)->getVariablesOfDataset($dataset);
        if (null === $variables)
           throw new HttpException(404, "There is no variable in this dataset.");

        $db_name = $this->getParameter('datasets_location').'/'.$dataset->getUuid().'.db';
        $filesystem = new Filesystem();
        $db=new \SQLite3($db_name);
        $sql = "SELECT ";
        $good = false;
        foreach($variables as $variable) {
            $good = true;
            $sql .= "`".$variable->getNameInDb() . "` AS `" . $variable->getName() . "`, ";
        }
        if (!$good)
            throw new HttpException(404, "There is no variable in this dataset.");
        $sql = rtrim($sql, ", ");
        //$sql .= " FROM data limit 100 offset :offset";
        $sql .= " FROM data";

        $data = array();
        try {
            $countItems = $db->query("SELECT COUNT(*) as count FROM data")->fetchArray()['count'];
            $stmt = $db->prepare($sql);
            //$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            $result = $stmt->execute();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                array_push($data, $row);
            }
        } catch (\Exception $ex) {
            throw new HttpException(500, $ex->getMessage());
        }

        return new JsonResponse([
            'itemsCount' => $countItems,
            'data' => $data
        ], 200);
    }

    /**
     * @Route("/api/current/dataset", name="my_datasets", methods={"GET"})
     */
    public function getMyDataSetsAction(Request $request)
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 12);
        $pager = $this->em->getRepository(DataSet::class)->findMyDataSets($page, $limit, $this->cr->getCurrentUser($this));
        $results = $pager->getCurrentPageResults();
        $nbPages = $pager->getNbPages();
        $currentPage = $pager->getCurrentPage();
        $maxPerPage = $pager->getMaxPerPage();
        $itemsCount = $pager->count();
        $datasets = array();
        foreach ($results as $result) {
            $datasets[] = $result;
        }
        $data = array('nbPages' => $nbPages, 'currentPage' => $currentPage, 'maxPerPage' => $maxPerPage, 'itemsCount' => $itemsCount, 'datasets' => $datasets);
        $data = $this->serializer->serialize($data, 'json', SerializationContext::create()->setGroups(array('my-dataset')));
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
    
    /**
     * @Route("/api/current/dataset", name="create_dataset", methods={"POST"})
     */
    public function createDataSetAction(Request $request)
    {
        $this->checkRoleAndId($request);

        return $this->form->validate($request, DataSet::class, array($this, 'setOwner'), array($this, 'doNothing'), ['new-dataset'], ['new-dataset']);
    }

    /**
     * @Route("/api/current/dataset/{uuid}/token", name="get_tokens_dataset", methods={"GET"}, requirements={"uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function getTokensDataSetAction(Request $request, $uuid)
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 20);

        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $dataset->getOwner())
            throw new HttpException(401, "You are not the owner.");

        $pager = $this->em->getRepository(SurveyToken::class)->findTokensOfDataset($page, $limit, $dataset);
        $results = $pager->getCurrentPageResults();
        $nbPages = $pager->getNbPages();
        $currentPage = $pager->getCurrentPage();
        $maxPerPage = $pager->getMaxPerPage();
        $itemsCount = $pager->count();
        $tokens = array();
        foreach ($results as $result) {
            $tokens[] = $result;
        }
        $data = array('nbPages' => $nbPages, 'currentPage' => $currentPage, 'maxPerPage' => $maxPerPage, 'itemsCount' => $itemsCount, 'tokens' => $tokens);
        $data = $this->serializer->serialize($data, 'json', SerializationContext::create()->setGroups(array('tokens')));
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/current/dataset/{uuid}/token/{uuidt}", name="specific_token_dataset", methods={"GET"}, requirements={"uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}", "uuidt"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function getTokenDataset($uuid, $uuidt)
    {
        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $dataset->getOwner())
            throw new HttpException(401, "You are not the owner.");

        $token = $this->em->getRepository(SurveyToken::class)->findOneBy(['uuid' => $uuidt, 'dataset' => $dataset]);
        if (null === $token)
            throw new HttpException(404, "Token not found.");

        return new JsonResponse([
            'code' => 200,
            'token' => $this->JWTencoder->encode(['dataset' => $dataset->getUuid(), 'survey' => $token->getUuid(), 'exp' => $token->getExpirationDate()->getTimestamp()])
        ], 200);
    }

    /**
     * @Route("/api/current/dataset/{uuid}/token/{uuidt}", name="delete_token_dataset", methods={"DELETE"}, requirements={"uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}", "uuidt"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function deleteTokenDataset($uuid, $uuidt)
    {
        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $dataset->getOwner())
            throw new HttpException(401, "You are not the owner.");

        $token = $this->em->getRepository(SurveyToken::class)->findOneBy(['uuid' => $uuidt, 'dataset' => $dataset]);
        if (null === $token)
            throw new HttpException(404, "Token not found.");

        try {
            $this->em->remove($token);
            $this->em->flush();
        } catch (\Exception $ex) {
            throw new HttpException(404, $ex->getMessage());
        }

        return new JsonResponse([
            'code' => 200,
            'token' => "Token deleted successfully."
        ], 200);
    }

    /**
     * @Route("/api/current/dataset/{uuid}/token", name="create_token_dataset", methods={"POST"}, requirements={"uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function createTokenToDataSetAction(Request $request, $uuid)
    {
        $data = $this->checkRoleAndId($request);

        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $dataset->getOwner())
            throw new HttpException(401, "You are not the owner.");

        if (!isset($data['privacy']))
            throw new HttpException(400, "Column privacy not found.");

        //need to implement privacy when is set to 0
        $message = "Good";
        if ($data['privacy'] === 0) {
            $surveyToken = new SurveyToken();
            $surveyToken->setPrivacy(0);
            $surveyToken->setDataset($dataset);
            try {
                if (isset($data['exp']) && strtotime($data['exp']) !== false){
                    $exp = new \DateTime();
                    $exp->setTimestamp(strtotime($data['exp']));
                    if ($exp> new \DateTime()) {
                        $surveyToken->setExpirationDate($exp);
                    } else
                        throw new HttpException(400, "Expiration date invalid.");
                } else
                    throw new HttpException(400, "Expiration date invalid.");
                $this->em->persist($surveyToken);
                $this->em->flush();
            } catch (\Exception $ex) {
                throw new HttpException(400, $ex->getMessage());
            }
            $message = $this->JWTencoder->encode(['dataset' => $dataset->getUuid(), 'survey' => $surveyToken->getUuid(), 'exp' => $surveyToken->getExpirationDate()->getTimestamp()]);
        }

        return new JsonResponse([
            'code' => 200,
            'token' => $message
        ], 200);
    }

    /**
     * @Route("/api/current/dataset/{uuid}/part", name="add_part_dataset", methods={"POST"}, requirements={"uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function addPartToDataSetAction(Request $request, $uuid)
    {
        $this->checkRoleAndId($request);

        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $dataset->getOwner())
            throw new HttpException(401, "You are not the owner.");

        return $this->form->validate($request, Part::class, array($this, 'setDataSetToObject'), array($this, 'afterPersistVariables'), ['new-part'], ['new-part'], null, $dataset);
    }

    /**
     * @Route("/api/current/dataset/{uuid}/part/{id}", name="update_part_dataset", methods={"PATCH"}, requirements={"id"="\d+", "uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function updatePartToDataSetAction(Request $request, $uuid, $id)
    {
        $this->checkRoleAndId($request);

        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $dataset->getOwner())
            throw new HttpException(401, "You are not the owner.");

        $part = $this->em->getRepository(Part::class)->findOneBy(['id' => $id, 'dataSet' => $dataset->getId()]);
        if (null === $part)
            throw new HttpException(404, "Part of dataset not found.");

        return $this->form->update($request, Part::class, array($this, 'doNothing'), array($this, 'doNothing'), ['update-part'], ['update-part'], $part->getId());
    }

    /**
     * @Route("/api/current/dataset/{uuid}/part/{id}", name="add_variables_dataset", methods={"POST"}, requirements={"id"="\d+", "uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function addVariablesAction(Request $request, $uuid, $id)
    {
        $this->checkRoleAndId($request);

        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $dataset->getOwner())
            throw new HttpException(401, "You are not the owner.");

        $part = $this->em->getRepository(Part::class)->findOneBy(['id' => $id, 'dataSet' => $dataset->getId()]);
        if (null === $part)
            throw new HttpException(404, "Part of dataset not found.");

        return $this->form->update($request, Part::class, array($this, 'addVariables'), array($this, 'afterPersistVariables'), ['add-variables'], ['add-variables'], $part->getId());
    }

    /**
     * @Route("/api/anon/dataset/{uuid}/parts", name="anon_get_dataset", methods={"GET"}, requirements={"uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function anonGetDataSetInfoAction($uuid)
    {
        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        $current = $this->cr->getCurrentUser($this);
        // if ($current != $dataset->getOwner())
        //     throw new HttpException(401, "You are not the owner");
        $data = $this->serializer->serialize($dataset, 'json', SerializationContext::create()->setGroups(array('my-dataset', 'my-dataset')));
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/anon/dataset/{uuid}/part/{id}", name="put_variables_dataset", methods={"POST"}, requirements={"id"="\d+", "uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function putVariablesAction(Request $request, $uuid, $id)
    {
        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $part = $this->em->getRepository(Part::class)->findOneBy(['id' => $id, 'dataSet' => $dataset->getId()]);
        if (null === $part)
            throw new HttpException(404, "Part of dataset not found.");

        $data = json_decode($request->getContent(), true);
        if (!array_key_exists('row', $data))
            throw new HttpException(400, "Could not found row field.");
        if (!array_key_exists('variables', $data) || !is_array($data['variables']))
            throw new HttpException(400, "Could not found variables array.");
        
        $sqlVariables = "";
        $sqlValues = "";
        $isUpdate = false;
        if (null !== $data['row'] && !ctype_digit($data['row']))
            $isUpdate = true;
        foreach($data['variables'] as $variable) {
            if (!is_array($variable) || !array_key_exists('id', $variable) || !array_key_exists('value', $variable))
                throw new HttpException(400, "Could not found variables array.");
            if (null === $variable['id'] || null === $variable['value'])
                throw new HttpException(400, "Could not found variables array.");
            if ("" === $variable['id'] || "" === $variable['value'])
                throw new HttpException(400, "Could not found variables array.");
            $realVariable = $this->em->getRepository(Variable::class)->findOneBy(['part' => $part, 'id' => $variable['id']]);
            if (null === $realVariable)
                throw new HttpException(400, "Some of the variables does not exists.");
            if ($isUpdate) {
                $sqlValues .= "`".$realVariable->getNameInDb().'`="'.$variable['value'].'",';
            } else {
                $sqlVariables .= "`".$realVariable->getNameInDb()."`,";
                $sqlValues .= '"'.$variable['value'].'",';
            }
        }
        $db_name = $this->getParameter('datasets_location').'/'.$dataset->getUuid().'.db';
        $filesystem = new Filesystem();
        if ($filesystem->exists($db_name))
            $filesystem->copy($db_name, $db_name.".copy", true);
        $db=new \SQLite3($db_name);
        if ($isUpdate)
            $sql = "UPDATE data SET ".rtrim($sqlValues, ", ")." WHERE rowid=".$data['row'].";";
        else
            $sql = "INSERT INTO data (".rtrim($sqlVariables, ", ").") VALUES(".rtrim($sqlValues, ", ").");";

        $extras = null;
        try {
            $db->exec($sql);
            if (!$isUpdate)
                $extras['row'] = $db->lastInsertRowid();
            $filesystem->remove($db_name.".copy");
        } catch (\Exception $ex) {
            $filesystem->remove($db_name.".copy");
            throw new HttpException(400, "Some of the variables does not exists.");
        }

        return new JsonResponse([
            'code' => 200,
            'message' => "Answers added successfully.",
            'extras' => $extras
        ], 200);
    }

    /**
     * @Route("/api/current/dataset/{uuid}/part/{id}/variable/{idVar}", name="edit_variable_dataset", methods={"PATCH"}, requirements={"id"="\d+", "idVar"="\d+", "uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function editVariableAction(Request $request, $uuid, $id, $idVar)
    {
        $this->checkRoleAndId($request);

        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $dataset->getOwner())
            throw new HttpException(401, "You are not the owner.");

        $part = $this->em->getRepository(Part::class)->findOneBy(['id' => $id, 'dataSet' => $dataset->getId()]);
        if (null === $part)
            throw new HttpException(404, "Part of dataset not found.");

        $variable = $this->em->getRepository(Variable::class)->findOneBy(['id' => $idVar, 'part' => $part->getId()]);
        if (null === $variable)
            throw new HttpException(404, "Variable not found.");

        return $this->form->update($request, Variable::class, array($this, 'doNothing'), array($this, 'doNothing'), ['update-variable'], ['update-variable'], $variable->getId());
    }

    /**
     * @Route("/api/current/dataset/{uuid}/part/{id}/variable/{idVar}", name="delete_variable_dataset", methods={"DELETE"}, requirements={"id"="\d+", "idVar"="\d+", "uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function deleteVariableAction(Request $request, $uuid, $id, $idVar)
    {
        $this->checkRoleAndId($request);

        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $dataset->getOwner())
            throw new HttpException(401, "You are not the owner.");

        $part = $this->em->getRepository(Part::class)->findOneBy(['id' => $id, 'dataSet' => $dataset->getId()]);
        if (null === $part)
            throw new HttpException(404, "Part of dataset not found.");

        $variable = $this->em->getRepository(Variable::class)->findOneBy(['id' => $idVar, 'part' => $part->getId()]);
        if (null === $variable)
            throw new HttpException(404, "Variable not found.");

        try{
            $this->em->remove($variable);
            $this->em->flush();
        } catch (\Exception $ex) {
            throw new HttpException(400, $ex->getMessage());
        }

        return new JsonResponse([
            'code' => 200,
            'message' => "Variable deleted successfully.",
        ], 200);
    }

    /**
     * @Route("/api/current/dataset/{uuid}/import", name="import_data_to_dataset", methods={"POST"}, requirements={"uuid"="[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}"})
     */
    public function importDataAction(Request $request, $uuid)
    {
        $data = $this->checkRoleAndId($request);

        $dataset = $this->em->getRepository(DataSet::class)->findOneBy(['uuid' => $uuid]);
        if (null === $dataset)
            throw new HttpException(404, "Dataset not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $dataset->getOwner())
            throw new HttpException(401, "You are not the owner.");

        $code = 401;
        $message = "Unauthorized";
        $extras = NULL;

        if (isset($data['file']) && null !== $data['file']) {
            $fileExcel = new FileExcel();
            $file = new UploadedBase64EncodedFile(new Base64EncodedFile($data['file']));
            $fileExcel->setFile($file);
            $fileExcel->setLink($file->getClientOriginalName());
            $fileExcel->setDataset($dataset);
            $dataset->setFileExcel($fileExcel);

            try {
                $this->em->persist($fileExcel);
                $this->em->persist($dataset);
                $this->em->flush();

                $code = 201;
                $message = 'Data imported successfully';
            } catch (HttpException $ex) {
                $code = $ex->getStatusCode();
                $message = $ex->getMessage();
            } catch (\Exception $ex) {
                $code = 400;
                $message = $ex->getMessage();
            }
        }

        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'extras' => $extras
        ], $code);
    }
}