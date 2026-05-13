<?php

declare(strict_types=1);

namespace ApiV2\Domain;

/**
 * Builds the createAssessment webhook payload from a standalone Culture
 * Assessment form submission. Mirrors the field mapping and scoring rules
 * in src/api/classes/traits/assess.php (the v1 LTRP+CA combined flow).
 *
 * Form values for each question are 'Yes' / 'No' / '' (unanswered). For
 * scoring purposes, only an explicit 'No' counts against the band — 'Yes'
 * and '' (empty) both pass through.
 */
final class CultureAssessmentPayload
{
    private const SCORE_BANDS = [
        'visionAndPractice' => [['VP01', 'VP02', 'VP03', 'VP04'], ['VP11', 'VP12', 'VP13', 'VP14']],
        'explicitTeaching'  => [['ET01', 'ET02', 'ET03', 'ET04'], ['ET11', 'ET12', 'ET13', 'ET14']],
        'habitBuilding'     => [['HB01', 'HB02', 'HB03', 'HB04'], ['HB11', 'HB12', 'HB13', 'HB14']],
        'staffCapacity'     => [['SC01', 'SC02', 'SC03'],         ['SC11', 'SC12', 'SC13']],
        'staffWellbeing'    => [['SW01', 'SW02'],                 ['SW11', 'SW12']],
        'familyCapacity'    => [['FC01', 'FC02'],                 ['FC11', 'FC12']],
        'partnerships'      => [['P01',  'P02'],                  ['P11',  'P12']],
    ];

    /**
     * Build the createAssessment webhook payload.
     *
     * @param array<string, mixed> $data Raw form submission
     */
    public static function build(string $organisationId, array $data): array
    {
        $payload = [
            'organisationId' => $organisationId,
            'assessmentName' => '2026 Wellbeing Culture Assessment',
            'orgType' => 'School - New',
        ];

        foreach (self::SCORE_BANDS as $field => [$zeroSet, $oneSet]) {
            $payload[$field] = self::score($data, $zeroSet, $oneSet);
        }

        foreach (self::questionKeys() as $key) {
            $payload[$key] = self::toBool($data, $key);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $data
     * @param string[]             $zeroSet  any 'No' here → "Emerging"
     * @param string[]             $oneSet   any 'No' here → "Established", else "Excelling"
     */
    private static function score(array $data, array $zeroSet, array $oneSet): string
    {
        foreach ($zeroSet as $key) {
            if (($data[$key] ?? '') === 'No') {
                return 'Emerging';
            }
        }
        foreach ($oneSet as $key) {
            if (($data[$key] ?? '') === 'No') {
                return 'Established';
            }
        }
        return 'Excelling';
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function toBool(array $data, string $key): bool
    {
        return ($data[$key] ?? '') === 'Yes';
    }

    /**
     * @return string[] All individual question keys, in the same order as the v1 trait.
     */
    private static function questionKeys(): array
    {
        $keys = [];
        foreach (self::SCORE_BANDS as [$zeroSet, $oneSet]) {
            foreach ($zeroSet as $k) {
                $keys[] = $k;
            }
            foreach ($oneSet as $k) {
                $keys[] = $k;
            }
        }
        return $keys;
    }
}
