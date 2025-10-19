<?php

declare(strict_types=1);

/*
 * This file is part of the proprietary project.
 *
 * This file and its contents are confidential and protected by copyright law.
 * Unauthorized copying, distribution, or disclosure of this content
 * is strictly prohibited without prior written consent from the author or
 * copyright owner.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Domain\Service;

final readonly class StatisticsCalculator
{
    /**
     * Oblicza podsumowanie statystyk na podstawie punktów danych.
     *
     * @param array<array{date: string, sessionId: string, maxWeightKg: float}> $dataPoints
     *
     * @return array{totalSessions: int, personalRecord: float, prDate: string, firstWeight: float, latestWeight: float, progressPercentage: float}
     */
    public function calculateSummary(array $dataPoints): array
    {
        if (empty($dataPoints)) {
            throw new \InvalidArgumentException('Cannot calculate summary for empty data points');
        }

        // Znajdź rekord personalny (PR)
        $personalRecord = 0.0;
        $prDate = '';
        foreach ($dataPoints as $point) {
            if ($point['maxWeightKg'] > $personalRecord) {
                $personalRecord = $point['maxWeightKg'];
                $prDate = $point['date'];
            }
        }

        // Pierwsza i ostatnia waga
        $firstWeight = $dataPoints[0]['maxWeightKg'];
        $latestWeight = $dataPoints[count($dataPoints) - 1]['maxWeightKg'];

        // Oblicz postęp procentowy
        $progressPercentage = $this->calculateProgressPercentage($firstWeight, $latestWeight);

        return [
            'totalSessions' => count($dataPoints),
            'personalRecord' => $personalRecord,
            'prDate' => $prDate,
            'firstWeight' => $firstWeight,
            'latestWeight' => $latestWeight,
            'progressPercentage' => $progressPercentage,
        ];
    }

    /**
     * Oblicza postęp procentowy między pierwszą a ostatnią wagą.
     */
    public function calculateProgressPercentage(float $firstWeight, float $latestWeight): float
    {
        if ($firstWeight <= 0) {
            return 0.0;
        }

        $progress = (($latestWeight - $firstWeight) / $firstWeight) * 100;

        return round($progress, 2);
    }
}

