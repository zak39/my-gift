<?php

namespace App\Controller\Me;

use App\Entity\User;
use App\Services\Export\ExportGiftList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/me/profile/export')]
final class DataExportController extends AbstractController
{
    /**
     * No requirement on the format: the formats ExportGiftList knows are the list, any
     * other is not found.
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/gifts.{format}', name: 'app_user_profile_export_gifts', methods: ['GET'])]
    public function gifts(string $format, ExportGiftList $exportGiftList): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $file = $exportGiftList($user, $format) ?? throw $this->createNotFoundException();

        $response = $this->file($file->path, $file->filename);
        // Its own: guessed from the content, a CSV would be text/plain and an XLSX a ZIP.
        $response->headers->set('Content-Type', $file->contentType);

        // Private: someone's data, no shared cache may keep it. Sent from the temporary
        // file, which goes once sent.
        return $response
            ->setPrivate()
            ->deleteFileAfterSend()
        ;
    }
}
