<?php

namespace App\EventListener;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;

class PhotoSerializerListener implements EventSubscriberInterface
{
	private $imagineCacheManager;

	public function __construct(\Liip\ImagineBundle\Imagine\Cache\CacheManager $cacheManager)
	{
		$this->imagineCacheManager = $cacheManager;
	}

	public static function getSubscribedEvents()
	{
		return array(
			array(
				'event' => 'serializer.post_serialize',
				'method' => 'onPostSerialize',
				'class' => 'App\Entity\Photo', // if no class, subscribe to every serialization
				'format' => 'json', // optional format
				'priority' => 0 // optional priority
			)
		);
	}

	public function onPostSerialize(ObjectEvent $event)
	{
		$link = $event->getObject()->getLink();
		$newLink = $this->imagineCacheManager->getBrowserPath($link, 'photo_scale_down');
		$thumbnail = $this->imagineCacheManager->getBrowserPath($link, 'photo_thumb');
		$visitor = $event->getVisitor();
		$visitor->visitProperty(new StaticPropertyMetadata('App\Entity\Photo', 'img', null), $newLink);
		$visitor->visitProperty(new StaticPropertyMetadata('App\Entity\Photo', 'thumbnail', null), $thumbnail);
	}
}
