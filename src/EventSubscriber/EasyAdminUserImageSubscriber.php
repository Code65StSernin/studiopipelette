<?php

namespace App\EventSubscriber;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EasyAdminUserImageSubscriber implements EventSubscriberInterface
{
    private string $uploadDir;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/users')] string $uploadDir
    ) {
        $this->uploadDir = $uploadDir;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['resizeUserImage'],
            BeforeEntityUpdatedEvent::class => ['resizeUserImage'],
        ];
    }

    public function resizeUserImage($event): void
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof User)) {
            return;
        }

        $imageFilename = $entity->getImage();

        if (!$imageFilename) {
            return;
        }

        $filePath = $this->uploadDir . '/' . $imageFilename;

        if (!file_exists($filePath)) {
            return;
        }

        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($filePath);
            
            // Resize to 300x300
            $image->resize(300, 300);
            
            // Save the resized image, overwriting the original
            $image->save($filePath);
        } catch (\Exception $e) {
            // Log error or handle it silently?
            // For now, we just let it be if resizing fails (e.g. invalid image)
        }
    }
}
