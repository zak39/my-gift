<?php

namespace App\Controller\Me;

use App\Entity\User;
use App\Services\Export\ExportAccountData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/me/profile/export')]
final class DataExportController extends AbstractController
{
    /**
     * No requirement on the format: the formats ExportAccountData knows are the list, any
     * other is not found. The format, not the extension: the CSV come zipped.
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{format}', name: 'app_user_profile_export', methods: ['GET'])]
    public function download(string $format, ExportAccountData $exportAccountData): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $file = $exportAccountData($user, $format) ?? throw $this->createNotFoundException();

        $response = $this->file($file->path, $file->filename);
        // Its own: guessed from the content, an XLSX would be a ZIP.
        $response->headers->set('Content-Type', $file->contentType);

        // Private: someone's data, no shared cache may keep it. Sent from the temporary
        // file, which goes once sent.
        return $response
            ->setPrivate()
            ->deleteFileAfterSend()
        ;
    }
}
