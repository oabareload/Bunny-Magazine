<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

if (!defined('ABSPATH')) {
	exit;
}

final class Utils
{
	public static function sanitize_id_list($value): array
	{
		if (is_array($value)) {
			$tokens = $value;
		} else {
			$tokens = preg_split('/[,\s]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
		}

		$ids = [];

		foreach ($tokens as $token) {
			$token = absint($token);
			if ($token > 0) {
				$ids[] = $token;
			}
		}

		$ids = array_values(array_unique(array_filter($ids)));
		sort($ids);

		return $ids;
	}

	public static function sanitize_author_id_list($value): array
	{
		return self::sanitize_id_list($value);
	}
}
