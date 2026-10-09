<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Services\Notifications;

final class NotificationController
{
    private const HISTORY_PER_PAGE = 20;

    public function index(): void
    {
        $this->markReadFromQuery('/notifications');

        view('notifications/index', [
            'pageTitle' => 'Notifications',
            'active'    => '',
            'heading'   => 'Notifications',
            'subtitle'  => 'Read your latest system notifications.',
            'items'     => Notifications::latest(Auth::id(), 5),
            'total'     => Notifications::count(Auth::id()),
            'cleared'   => input('cleared', '') !== '',
            'base'      => '/notifications',
            'pager'     => null,
        ]);
    }

    public function history(): void
    {
        $this->markReadFromQuery('/notifications/history');

        $total = Notifications::count(Auth::id());
        $pager = paginate($total, self::HISTORY_PER_PAGE, (int)input('page', 1));

        view('notifications/index', [
            'pageTitle' => 'Notification History',
            'active'    => '',
            'heading'   => 'Notification History',
            'subtitle'  => 'View all your system notifications.',
            'items'     => Notifications::latest(Auth::id(), $pager['perPage'], $pager['offset']),
            'total'     => $total,
            'cleared'   => input('cleared', '') !== '',
            'base'      => '/notifications/history',
            'pager'     => $pager,
        ]);
    }

    public function clear(): void
    {
        Notifications::clear(Auth::id());
        $back = input('back') === 'history' ? '/notifications/history' : '/notifications';
        redirect($back . '?cleared=1');
    }

    /** Opening a notification link (?id=) marks it read, then shows the list. */
    private function markReadFromQuery(string $back): void
    {
        $id = (int)input('id', 0);
        if ($id > 0) {
            Notifications::markRead(Auth::id(), $id);
            redirect($back);
        }
    }
}
