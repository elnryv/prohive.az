<?php
declare(strict_types=1);

/**
 * Ev sahibi kartı (9.3): status keçidi bir kliklə, səbəbsiz, ANINDA qüvvəyə minir (Q9).
 * Bütün əməliyyatlar admin_logs-a yazılır.
 */
final class OwnerCard
{
    public function show(array $params): void
    {
        AdminAuth::requireLogin();
        $owner = $this->ownerOrDie($params);

        View::render('admin/owner_card', [
            'title' => $owner['full_name'] . ' — Admin',
            'owner' => $owner,
            'houses' => OwnerRepository::houses((int) $owner['id']),
            'payments' => OwnerRepository::paymentsHistory((int) $owner['id']),
            'effectivePrice' => OwnerRepository::effectivePrice($owner),
            'defaultPrice' => SettingsRepository::getFloat('default_monthly_price', 25.0),
            'isVisible' => OwnerRepository::isVisible($owner),
        ], 'layout');
    }

    public function setFree(array $params): void
    {
        $owner = $this->ownerOrDieCsrf($params);
        OwnerRepository::setFree((int) $owner['id']);
        AdminLog::write('owner.set_free', (int) $owner['id'], $owner['phone']);
        $this->back((int) $owner['id']);
    }

    public function setPaid(array $params): void
    {
        $owner = $this->ownerOrDieCsrf($params);

        $priceRaw = trim((string) ($_POST['custom_price'] ?? ''));
        $price = ($priceRaw !== '' && is_numeric($priceRaw)) ? (float) $priceRaw : null;

        OwnerRepository::makeBillable((int) $owner['id'], $price);
        AdminLog::write('owner.set_paid', (int) $owner['id'], $price !== null ? "custom_price={$price}" : 'default_price');
        $this->back((int) $owner['id']);
    }

    public function clearPrice(array $params): void
    {
        $owner = $this->ownerOrDieCsrf($params);
        OwnerRepository::clearCustomPrice((int) $owner['id']);
        AdminLog::write('owner.clear_custom_price', (int) $owner['id']);
        $this->back((int) $owner['id']);
    }

    public function block(array $params): void
    {
        $owner = $this->ownerOrDieCsrf($params);
        OwnerRepository::block((int) $owner['id']);
        AdminLog::write('owner.block', (int) $owner['id'], $owner['phone']);
        $this->back((int) $owner['id']);
    }

    public function activate(array $params): void
    {
        $owner = $this->ownerOrDieCsrf($params);
        OwnerRepository::activate((int) $owner['id']);
        AdminLog::write('owner.activate', (int) $owner['id'], $owner['phone']);
        $this->back((int) $owner['id']);
    }

    private function ownerOrDie(array $params): array
    {
        $owner = OwnerRepository::findById((int) ($params['id'] ?? 0));
        if ($owner === null) {
            http_response_code(404);
            View::render('admin/placeholder', ['title' => '404 — Admin']);
            exit;
        }
        return $owner;
    }

    private function ownerOrDieCsrf(array $params): array
    {
        AdminAuth::requireLogin();
        $owner = $this->ownerOrDie($params);
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $this->back((int) $owner['id']);
        }
        return $owner;
    }

    private function back(int $ownerId): void
    {
        header("Location: /owners/{$ownerId}");
        exit;
    }
}
