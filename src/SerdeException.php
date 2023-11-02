<?php

declare(strict_types=1);

namespace Crell\Serde;

/**
 * Marker interface for all Serde-thrown exceptions.
 *
 * It exists primarily to make it possible to catch
 * any Serde exception not otherwise handled, and handle
 * it accordingly.
 */
interface SerdeException {}
