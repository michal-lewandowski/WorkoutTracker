<?php

declare(strict_types=1);

namespace App\Domain\Service;

final readonly class StatisticsCalculator
{
    /**
     * @param array<array{date: string, sessionId: string, maxWeightKg: float, totalVolumeKg: float}> $dataPoints
     *
     * @return array{totalSessions: int, personalWeightRecord: float, prDateMaxWeight: string, personalVolumeRecord: float, prDateMaxVolume: string,  firstWeight: float, latestWeight: float, progressPercentage: float}
     */
    public function calculateSummary(array $dataPoints): array
    {
        if (empty($dataPoints)) {
            throw new \InvalidArgumentException('Cannot calculate summary for empty data points');
        }

        $personalWeightRecord = 0.0;
        $personalVolumeRecord = 0.0;
        $prDateMaxWeight = '';
        $prDateMaxVolume = '';
        foreach ($dataPoints as $point) {
            if ($point['maxWeightKg'] > $personalWeightRecord) {
                $personalWeightRecord = $point['maxWeightKg'];
                $prDateMaxWeight = $point['date'];
            }
            if ($point['totalVolumeKg'] > $personalVolumeRecord) {
                $personalVolumeRecord = $point['totalVolumeKg'];
                $prDateMaxVolume = $point['date'];
            }
        }

        $firstWeight = $dataPoints[0]['maxWeightKg'];
        $latestWeight = $dataPoints[count($dataPoints) - 1]['maxWeightKg'];

        $progressPercentage = $this->calculateProgressPercentage($firstWeight, $latestWeight);

        return [
            'totalSessions' => count($dataPoints),
            'personalWeightRecord' => $personalWeightRecord,
            'personalVolumeRecord' => $personalVolumeRecord,
            'prDateMaxWeight' => $prDateMaxWeight,
            'prDateMaxVolume' => $prDateMaxVolume,
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
