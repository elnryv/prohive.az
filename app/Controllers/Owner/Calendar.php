<?php
declare(strict_types=1);

/** Təqvim (7.4, Q4): ev sahibi dolu/boş günləri özü işarələyir — vitrin məlumatıdır, rezervasiya deyil. */
final class Calendar
{
    public function index(): void
    {
        Auth::requireLogin();
        $owner = Auth::user();
        $houses = HouseRepository::forOwner((int) $owner['id']);

        $selectedId = (int) ($_GET['ev'] ?? ($houses[0]['id'] ?? 0));
        $selected = null;
        foreach ($houses as $h) {
            if ((int) $h['id'] === $selectedId) {
                $selected = $h;
                break;
            }
        }
        if ($selected === null && $houses !== []) {
            $selected = $houses[0];
            $selectedId = (int) $selected['id'];
        }

        $busy = [];
        if ($selected !== null) {
            $from = (new DateTimeImmutable('first day of this month'))->format('Y-m-d');
            $to = (new DateTimeImmutable('first day of this month'))->modify('+5 months')->modify('last day of this month')->format('Y-m-d');
            $busy = array_flip(HouseRepository::busyDates($selectedId, $from, $to));
        }

        View::render('owner/calendar', [
            'title' => Lang::t('owner.calendar_title') . ' — ' . Lang::t('app.name'),
            'houses' => $houses,
            'selectedId' => $selectedId,
            'busy' => $busy,
        ], 'layout');
    }

    public function toggleDay(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $house = $this->ownedHouseOrJsonError($params);
        if ($house === null) {
            return;
        }
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'csrf']);
            return;
        }

        $date = (string) ($_POST['date'] ?? '');
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($d === false || $d->format('Y-m-d') !== $date) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'invalid_date']);
            return;
        }

        $busy = HouseRepository::toggleBusyDay((int) $house['id'], $date);
        echo json_encode(['ok' => true, 'busy' => $busy]);
    }

    public function toggleRange(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $house = $this->ownedHouseOrJsonError($params);
        if ($house === null) {
            return;
        }
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'csrf']);
            return;
        }

        $from = (string) ($_POST['from'] ?? '');
        $to = (string) ($_POST['to'] ?? '');
        $busy = !empty($_POST['busy']);

        $fromD = DateTimeImmutable::createFromFormat('Y-m-d', $from);
        $toD = DateTimeImmutable::createFromFormat('Y-m-d', $to);
        if ($fromD === false || $toD === false || $fromD > $toD) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'invalid_range']);
            return;
        }

        HouseRepository::markRange((int) $house['id'], $from, $to, $busy);
        echo json_encode(['ok' => true]);
    }

    private function ownedHouseOrJsonError(array $params): ?array
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'auth']);
            return null;
        }
        $owner = Auth::user();
        $house = HouseRepository::findOwnedById((int) ($params['id'] ?? 0), (int) $owner['id']);
        if ($house === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'not_found']);
            return null;
        }
        return $house;
    }
}
