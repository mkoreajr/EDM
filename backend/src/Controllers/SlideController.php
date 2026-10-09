<?php

namespace App\Controllers;

use App\Core\DB;

/**
 * Serves login slideshow images stored in PostgreSQL (they survive redeploys).
 * A slide id never changes content, so responses are cached for a year.
 */
final class SlideController
{
    public function image(): void
    {
        $id = (int)input('id', 0);
        $row = $id > 0 ? DB::one('SELECT mime_type, image_data FROM login_slides WHERE id = ?', [$id]) : null;
        if ($row === null || empty($row['image_data'])) {
            http_response_code(404);
            return;
        }

        $data = (string)$row['image_data'];
        $mime = (string)($row['mime_type'] ?: 'image/jpeg');
        if (preg_match('/^data:([^;]+);base64,(.*)$/s', $data, $m)) {
            $mime = $m[1] ?: $mime;
            $data = $m[2];
        }

        $binary = base64_decode($data, true);
        if ($binary === false) {
            http_response_code(500);
            return;
        }

        $etag = '"slide-' . $id . '-' . strlen($binary) . '"';
        header('Cache-Control: public, max-age=31536000, immutable');
        header('ETag: ' . $etag);
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            return;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($binary));
        echo $binary;
    }
}
