<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A stock rule was broken (not enough stock, product removed, transaction already decided).
 * The message is safe to show to the user.
 */
class StockException extends RuntimeException {}
