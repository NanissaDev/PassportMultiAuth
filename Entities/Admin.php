<?php

namespace Nanissa\PassportMultiAuth\Entities;

use App\User;
use Parental\HasParent;

class Admin extends User
{
    use HasParent;
}
