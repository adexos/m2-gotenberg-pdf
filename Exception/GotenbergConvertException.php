<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Exception;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class GotenbergConvertException extends LocalizedException
{
  public function __construct(Phrase $phrase, Exception $cause = null)
  {
      parent::__construct(
          $phrase,
          $cause,
          400
      );
  }
}
