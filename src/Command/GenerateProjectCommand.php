<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:knh-project',
    description: 'Clone complètement le projet parent avec toute sa configuration'
)]
class GenerateProjectCommand extends Command
{
    private Filesystem $fs;

    public function __construct()
    {
        parent::__construct();
        $this->fs = new Filesystem();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Nom du nouveau projet')
            ->addArgument('target-dir', InputArgument::OPTIONAL, 'Répertoire cible', getcwd());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $input->getArgument('name');
        $targetDir = rtrim($input->getArgument('target-dir'), '/');
        $projectDir = $targetDir.'/'.$projectName;
        
        if ($this->fs->exists($projectDir)) {
            $output->writeln("<error>Le dossier $projectDir existe déjà!</error>");
            return Command::FAILURE;
        }

        $parentProjectDir = dirname(__DIR__, 3);
        $output->writeln("<info>Clonage du projet parent depuis $parentProjectDir vers $projectDir...</info>");

        // 1. Création du dossier de destination
        $this->fs->mkdir($projectDir);

        // 2. Copie de tous les fichiers
        $this->copyDirectory($parentProjectDir, $projectDir, $output);

        // 3. Nettoyage du nouveau projet
        $this->cleanupProject($projectDir, $output);

        // 4. Initialisation du nouveau projet
        $this->initializeNewProject($projectDir, $projectName, $output);

        $output->writeln("<info>Projet $projectName cloné avec succès dans $projectDir!</info>");
        $output->writeln("<comment>N'oubliez pas de configurer le .env et composer.json</comment>");

        return Command::SUCCESS;
    }

    private function copyDirectory(string $source, string $destination, OutputInterface $output): void
    {
        $output->writeln("Copie des fichiers depuis $source...");
        
        $excludedDirs = [
            'var', 
            'vendor', 
            'node_modules', 
            '.git', 
            '.idea',
            basename($destination),
            'src/Controller/Apis' // Exclusion spécifique du dossier problématique
        ];

        $finder = new Finder();
        $finder->in($source)
            ->ignoreDotFiles(false)
            ->exclude($excludedDirs)
            ->notPath('TestingController.php') // Exclusion spécifique du fichier problématique
            ->depth(0);

        foreach ($finder as $item) {
            $targetPath = $destination.'/'.$item->getFilename();

            if ($item->isDir()) {
                $this->fs->mirror($item->getPathname(), $targetPath, null, [
                    'override' => true,
                    'delete' => true,
                    'exclude' => $excludedDirs
                ]);
            } else {
                try {
                    $this->fs->copy($item->getPathname(), $targetPath);
                } catch (\Exception $e) {
$this->setStatusCode(500);
                    $output->writeln("<error>Erreur lors de la copie de {$item->getPathname()}: {$e->getMessage()}</error>");
                }
            }
        }
    }

    private function cleanupProject(string $projectDir, OutputInterface $output): void
    {
        $output->writeln("Nettoyage du nouveau projet...");
        
        // Suppression des dossiers inutiles
        $dirsToRemove = [
            $projectDir.'/var',
            $projectDir.'/.idea',
            $projectDir.'/.git'
        ];
        
        foreach ($dirsToRemove as $dir) {
            if ($this->fs->exists($dir)) {
                $this->fs->remove($dir);
            }
        }
        
        // Réinitialisation du fichier .env
        $envFile = $projectDir.'/.env';
        if ($this->fs->exists($envFile)) {
            $envContent = file_get_contents($envFile);
            $envContent = preg_replace('/^(\w+=).*$/m', '$1', $envContent);
            $this->fs->dumpFile($envFile, $envContent);
        }
    }

    private function initializeNewProject(string $projectDir, string $projectName, OutputInterface $output): void
    {
        $output->writeln("Initialisation du nouveau projet...");
        
        // Mise à jour du composer.json
        $composerFile = $projectDir.'/composer.json';
        if ($this->fs->exists($composerFile)) {
            $composerJson = json_decode(file_get_contents($composerFile), true);
            if (JSON_ERROR_NONE === json_last_error()) {
                $composerJson['name'] = strtolower(str_replace(' ', '-', $projectName));
                $this->fs->dumpFile($composerFile, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $output->writeln("<error>Erreur lors de la lecture du composer.json: ".json_last_error_msg()."</error>");
            }
        }
        
        // Initialisation du dépôt Git
        $process = new Process(['git', 'init'], $projectDir);
        $process->run();
        
        if (!$process->isSuccessful()) {
            $output->writeln("<warning>Échec de l'initialisation du dépôt Git: ".$process->getErrorOutput()."</warning>");
        }
    }
}