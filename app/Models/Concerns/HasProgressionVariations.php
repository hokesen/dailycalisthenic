<?php

namespace App\Models\Concerns;

trait HasProgressionVariations
{
    /**
     * Get variations by traversing a relationship direction
     */
    protected function getVariations(string $relationshipName): array
    {
        $variations = [];
        $current = $this;

        while ($current->progression && $current->progression->{$relationshipName}) {
            $variation = $current->progression->{$relationshipName};
            $variations[] = $variation;
            $current = $variation;
        }

        return $variations;
    }

    /**
     * Get easier exercise variations
     */
    public function getEasierVariations(): array
    {
        return $this->getVariations('easierExercise');
    }

    /**
     * Get harder exercise variations
     */
    public function getHarderVariations(): array
    {
        return $this->getVariations('harderExercise');
    }
}
