<?php

namespace App\EventListener;

use App\Entity\Searcher;
use App\Entity\Notification;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Doctrine\ORM\EntityManagerInterface;

class ProfileSerializerListener implements EventSubscriberInterface
{
	private $em;

	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	public static function getSubscribedEvents()
	{
		return array(
			array(
				'event' => 'serializer.post_serialize',
				'method' => 'onPostSerialize',
				'class' => 'App\Entity\Customer', // if no class, subscribe to every serialization
				'format' => 'json', // optional format
				'priority' => 0, // optional priority
			),
			array(
				'event' => 'serializer.post_serialize',
				'method' => 'onPostSerialize',
				'class' => 'App\Entity\Searcher', // if no class, subscribe to every serialization
				'format' => 'json', // optional format
				'priority' => 0, // optional priority
			)
		);
	}

	public function onPostSerialize(ObjectEvent $event)
	{
		$profile = $event->getObject();
		$visitor = $event->getVisitor();
		if ($profile instanceof Searcher) {
			$countProblematics = count($profile->getProblematics());
			$countComments = count($profile->getComments());
			$countFollowers = count($profile->getFollowers());
			$visitor->visitProperty(new StaticPropertyMetadata('App\Entity\Searcher', 'countProblematics', null), $countProblematics);
			$visitor->visitProperty(new StaticPropertyMetadata('App\Entity\Searcher', 'countComments', null), $countComments);
			$visitor->visitProperty(new StaticPropertyMetadata('App\Entity\Searcher', 'countFollowers', null), $countFollowers);
		}
		try {
			$groups = $event->getContext()->getAttribute('groups');
		}catch (\Exception $ex) {
			$groups = [];
		}
		if (!in_array('infos', $groups))
			return ;
		$countNotification = $this->em->getRepository(Notification::class)->getCountNotSeen($profile)["1"];
		$visitor->visitProperty(new StaticPropertyMetadata('App\Entity\User', 'countNotifications', null), $countNotification);
	}
}
