<?php

namespace Drupal\bottle;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

interface BottleInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {
    
}
