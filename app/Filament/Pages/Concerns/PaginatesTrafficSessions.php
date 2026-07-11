<?php

namespace App\Filament\Pages\Concerns;

trait PaginatesTrafficSessions
{
    public int $sessionsPage = 1;

    public int $sessionsPerPage = 5;

    public function getTotalSessionPages(): int
    {
        $total = count($this->getSelectedDateSessionsProperty());

        return max(
            1,
            (int) ceil($total / $this->sessionsPerPage)
        );
    }

    public function getPaginatedSessionsProperty(): array
    {
        return array_slice(
            $this->getSelectedDateSessionsProperty(),
            ($this->sessionsPage - 1) * $this->sessionsPerPage,
            $this->sessionsPerPage
        );
    }

    public function nextSessionsPage(): void
    {
        if ($this->sessionsPage >= $this->getTotalSessionPages()) {
            return;
        }

        $this->sessionsPage++;
    }

    public function previousSessionsPage(): void
    {
        if ($this->sessionsPage <= 1) {
            return;
        }

        $this->sessionsPage--;
    }

    protected function resetSessionsPagination(): void
    {
        $this->sessionsPage = 1;
    }
}