<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfReportService
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Generate an employee performance PDF report.
     *
     * @param User       $employee   The employee to generate the report for
     * @param Activity[] $activities  The employee's activities
     * @return string  The raw PDF content
     */
    public function generateEmployeeReport(User $employee, array $activities): string
    {
        // Compute statistics
        $total = count($activities);
        $approved = 0;
        $rejected = 0;
        $pending = 0;
        $onTime = 0;
        $late = 0;
        $totalHours = 0.0;
        $totalTrackedSeconds = 0;
        $projectBreakdown = [];

        foreach ($activities as $a) {
            $status = $a->getStatus();
            if ($status === 'APPROVED') $approved++;
            elseif ($status === 'REJECTED') $rejected++;
            else $pending++;

            if ($a->isOnTime()) {
                $onTime++;
            } else {
                $late++;
            }

            $totalHours += (float)($a->getHoursWorked() ?? 0);
            $totalTrackedSeconds += $a->getTimeSpent();

            // Project breakdown
            $pName = $a->getProject() ? $a->getProject()->getName() : 'Sans projet';
            if (!isset($projectBreakdown[$pName])) {
                $projectBreakdown[$pName] = ['total' => 0, 'approved' => 0, 'hours' => 0.0, 'tracked' => 0];
            }
            $projectBreakdown[$pName]['total']++;
            if ($status === 'APPROVED') $projectBreakdown[$pName]['approved']++;
            $projectBreakdown[$pName]['hours'] += (float)($a->getHoursWorked() ?? 0);
            $projectBreakdown[$pName]['tracked'] += $a->getTimeSpent();
        }

        $onTimePercentage = $total > 0 ? round(($onTime / $total) * 100) : 100;
        $approvalRate = $total > 0 ? round(($approved / $total) * 100) : 0;

        // Format tracked time
        $trackedHours = intdiv($totalTrackedSeconds, 3600);
        $trackedMinutes = intdiv($totalTrackedSeconds % 3600, 60);
        $trackedFormatted = $trackedHours . 'h ' . str_pad($trackedMinutes, 2, '0', STR_PAD_LEFT) . 'min';

        // Determine performance badge
        if ($onTimePercentage >= 90 && $approvalRate >= 80) {
            $badge = ['label' => '⭐ Excellent', 'color' => '#10b981'];
        } elseif ($onTimePercentage >= 70 && $approvalRate >= 60) {
            $badge = ['label' => '✅ Bon', 'color' => '#6366f1'];
        } elseif ($onTimePercentage >= 50) {
            $badge = ['label' => '⚠️ À améliorer', 'color' => '#f59e0b'];
        } else {
            $badge = ['label' => '🔴 Insuffisant', 'color' => '#ef4444'];
        }

        // Render template
        $html = $this->twig->render('back/reports/employee_pdf.html.twig', [
            'employee' => $employee,
            'activities' => $activities,
            'stats' => [
                'total' => $total,
                'approved' => $approved,
                'rejected' => $rejected,
                'pending' => $pending,
                'onTime' => $onTime,
                'late' => $late,
                'onTimePercentage' => $onTimePercentage,
                'approvalRate' => $approvalRate,
                'totalHours' => $totalHours,
                'trackedFormatted' => $trackedFormatted,
                'badge' => $badge,
            ],
            'projectBreakdown' => $projectBreakdown,
            'generatedAt' => new \DateTime(),
        ]);

        // Generate PDF
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
