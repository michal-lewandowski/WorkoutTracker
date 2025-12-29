<?php

declare(strict_types=1);

namespace App\Domain\Service;

final readonly class StatisticsCalculator
{
    /**
     * @param array<array{date: string, sessionId: string, maxWeightKg: float}> $dataPoints
     *
     * @return array{totalSessions: int, personalRecord: float, prDate: string, firstWeight: float, latestWeight: float, progressPercentage: float}
     */
    public function calculateSummary(array $dataPoints): array
    {
        if (empty($dataPoints)) {
            throw new \InvalidArgumentException('Cannot calculate summary for empty data points');
        }

        $personalRecord = 0.0;
        $prDate = '';
        foreach ($dataPoints as $point) {
            if ($point['maxWeightKg'] > $personalRecord) {
                $personalRecord = $point['maxWeightKg'];
                $prDate = $point['date'];
            }
        }

        $firstWeight = $dataPoints[0]['maxWeightKg'];
        $latestWeight = $dataPoints[count($dataPoints) - 1]['maxWeightKg'];

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

    public function calculateProgressPercentage(float $firstWeight, float $latestWeight): float
    {
        if ($firstWeight <= 0) {
            return 0.0;
        }

        $progress = (($latestWeight - $firstWeight) / $firstWeight) * 100;

        return round($progress, 2);
    }
}
