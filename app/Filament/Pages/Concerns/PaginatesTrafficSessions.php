<?php

namespace App\Filament\Pages\Concerns;

trait PaginatesTrafficSessions
{
    public int $sessionsPage = 1;

    public int $sessionsPerPage = 10;

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

    public function goToSessionsPage(int $page): void
    {
        $this->sessionsPage = max(
            1,
            min($page, $this->getTotalSessionPages())
        );
    }

    public function firstSessionsPage(): void
    {
        $this->goToSessionsPage(1);
    }

    public function previousSessionsPage(): void
    {
        $this->goToSessionsPage(
            $this->sessionsPage - 1
        );
    }

    public function nextSessionsPage(): void
    {
        $this->goToSessionsPage(
            $this->sessionsPage + 1
        );
    }

    public function lastSessionsPage(): void
    {
        $this->goToSessionsPage(
            $this->getTotalSessionPages()
        );
    }

    protected function resetSessionsPagination(): void
    {
        $this->goToSessionsPage(1);
    }
}