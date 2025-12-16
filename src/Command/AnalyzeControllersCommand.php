<?php

namespace App\Command;

use App\Service\ControllerAnalysisService;
use App\Service\ValidationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:analyze-controllers',
    description: 'Analyze API controllers for non-compliant patterns'
)]
class AnalyzeControllersCommand extends Command
{
    private ControllerAnalysisService $analysisService;
    private ValidationService $validationService;
    
    public function __construct(
        ControllerAnalysisService $analysisService,
        ValidationService $validationService
    ) {
        parent::__construct();
        $this->analysisService = $analysisService;
        $this->validationService = $validationService;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Controller Analysis Report');
        
        // Analyser tous les contrôleurs
        $io->section('Analyzing Controllers...');
        $analysisResults = $this->analysisService->analyzeAllControllers();
        $report = $this->analysisService->generateReport($analysisResults);
        
        // Afficher le résumé
        $io->section('Summary');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Controllers Analyzed', $report['summary']['totalControllersAnalyzed']],
                ['Non-Compliant Methods Found', $report['summary']['totalNonCompliantMethods']],
                ['find($id) patterns', $report['summary']['patternCounts']['find($id)']],
                ['findOneBy([\'id\' => $id]) patterns', $report['summary']['patternCounts']['findOneBy([\'id\' => $id])']]
            ]
        );
        
        // Afficher les détails si des problèmes sont trouvés
        if (!empty($analysisResults)) {
            $io->section('Non-Compliant Controllers');
            
            foreach ($analysisResults as $controllerName => $result) {
                $io->text("<fg=yellow>Controller:</> {$controllerName}");
                $io->text("<fg=blue>Path:</> {$result['path']}");
                
                if (!empty($result['analysis']['nonCompliantMethods'])) {
                    $io->text("<fg=red>Non-compliant patterns found:</>");
                    
                    foreach ($result['analysis']['nonCompliantMethods'] as $method) {
                        $io->text("  - Line {$method['line']}: {$method['pattern']} ({$method['type']})");
                    }
                }
                
                if (!empty($result['analysis']['routesWithId'])) {
                    $io->text("<fg=cyan>Routes with {id} parameter:</>");
                    foreach ($result['analysis']['routesWithId'] as $route) {
                        $io->text("  - Line {$route['line']}: {$route['route']}");
                    }
                }
                
                $io->newLine();
            }
        } else {
            $io->success('All controllers are compliant! 🎉');
        }
        
        // Générer le rapport de conformité
        $io->section('Compliance Report');
        $complianceReport = $this->validationService->generateComplianceReport();
        
        $io->table(
            ['Metric', 'Value'],
            [
                ['Status', $complianceReport['status']],
                ['Total Controllers', $complianceReport['summary']['totalControllers']],
                ['Compliant Controllers', $complianceReport['summary']['compliantControllers']],
                ['Non-Compliant Controllers', $complianceReport['summary']['nonCompliantControllers']],
                ['Compliance Percentage', $complianceReport['summary']['compliancePercentage'] . '%']
            ]
        );
        
        return Command::SUCCESS;
    }
}