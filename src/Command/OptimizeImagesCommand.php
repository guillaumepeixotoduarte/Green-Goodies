<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:optimize-images',
    description: 'Convertit les images du dossier product_img en WebP et les redimensionne.',
)]
class OptimizeImagesCommand extends Command
{
    private string $projectDir;

    public function __construct(ParameterBagInterface $params)
    {
        parent::__construct();
        $this->projectDir = $params->get('kernel.project_dir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 🚀 ASTUCE : On augmente temporairement la mémoire de PHP à 512 Mo
        // uniquement le temps que cette commande s'exécute.
        ini_set('memory_limit', '512M');

        $io = new SymfonyStyle($input, $output);
        $folderPath = $this->projectDir . '/public/product_img';

        if (!is_dir($folderPath)) {
            $io->error("Le dossier '$folderPath' n'existe pas.");
            return Command::FAILURE;
        }

        $files = array_diff(scandir($folderPath), ['.', '..']);
        $count = 0;

        $io->title("Démarrage de l'optimisation des images (Limite mémoire augmentée à 512M)...");

        foreach ($files as $file) {
            $filePath = $folderPath . '/' . $file;
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
                continue;
            }

            $io->text("Traitement de : $file...");

            $imageSource = null;
            if ($extension === 'jpg' || $extension === 'jpeg') {
                $imageSource = @imagecreatefromjpeg($filePath);
            } elseif ($extension === 'png') {
                $imageSource = @imagecreatefrompng($filePath);
                if ($imageSource) {
                    imagealphablending($imageSource, false);
                    imagesavealpha($imageSource, true);
                }
            }

            if (!$imageSource) {
                $io->warning("Impossible de lire le fichier $file (image corrompue, trop lourde ou format non supporté).");
                continue;
            }

            $width = imagesx($imageSource);
            $height = imagesy($imageSource);
            $maxWidth = 1000;

            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = (int) floor($height * ($maxWidth / $width));

                $imageResized = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($imageResized, false);
                imagesavealpha($imageResized, true);

                imagecopyresampled($imageResized, $imageSource, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                // 🔥 On détruit TOUT DE SUITE l'ancienne ressource lourde pour libérer la mémoire vive
                imagedestroy($imageSource);
                $imageSource = $imageResized;
            }

            $newFileName = pathinfo($file, PATHINFO_FILENAME) . '.webp';
            $destinationPath = $folderPath . '/' . $newFileName;

            if (imagewebp($imageSource, $destinationPath, 75)) {
                imagedestroy($imageSource);
                unlink($filePath);
                $count++;
            } else {
                imagedestroy($imageSource);
                $io->error("Échec de la conversion pour $file.");
            }

            // ♻️ On force PHP à nettoyer la mémoire immédiatement entre deux images
            gc_collect_cycles();
        }

        $io->success("$count image(s) ont été convertie(s) avec succès en WebP !");

        return Command::SUCCESS;
    }
}
