<?php
/**
 *  Copyright 2015 Ye Wenbin
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
namespace PhalconX\Test\Models;

/**
 * The model Tag.
 *
 * NOTE: This class is auto generated by the swagger code generator program. Do not edit the class manually.
 */
class Tag implements \ArrayAccess {
    /**
     * The id.
     * 
     * @Valid(type=integer)
     */
    public $id; /* int */

    /**
     * The name.
     */
    public $name; /* string */

    public function __construct(array $data = null) {
        if ($data === null) {
            return;
        }
        if (isset($data["id"])) {
            $this->id = $data["id"];
        }
        if (isset($data["name"])) {
            $this->name = $data["name"];
        }
    }
  
    public function offsetExists($offset) {
         return isset($this->$offset);
    }
  
    public function offsetGet($offset) {
         return $this->$offset;
    }
  
    public function offsetSet($offset, $value) {
         $this->$offset = $value;
    }
  
    public function offsetUnset($offset) {
         unset($this->$offset);
    }
}
