<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: opentelemetry/proto/profiles/v1development/profiles.proto

namespace Opentelemetry\Proto\Profiles\V1development;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Details a specific line in a source code, linked to a function.
 *
 * Generated from protobuf message <code>opentelemetry.proto.profiles.v1development.Line</code>
 */
class Line extends \Google\Protobuf\Internal\Message
{
    /**
     * Reference to function in Profile.function_table.
     *
     * Generated from protobuf field <code>int32 function_index = 1;</code>
     */
    protected $function_index = 0;
    /**
     * Line number in source code.
     *
     * Generated from protobuf field <code>int64 line = 2;</code>
     */
    protected $line = 0;
    /**
     * Column number in source code.
     *
     * Generated from protobuf field <code>int64 column = 3;</code>
     */
    protected $column = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $function_index
     *           Reference to function in Profile.function_table.
     *     @type int|string $line
     *           Line number in source code.
     *     @type int|string $column
     *           Column number in source code.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Opentelemetry\Proto\Profiles\V1Development\Profiles::initOnce();
        parent::__construct($data);
    }

    /**
     * Reference to function in Profile.function_table.
     *
     * Generated from protobuf field <code>int32 function_index = 1;</code>
     * @return int
     */
    public function getFunctionIndex()
    {
        return $this->function_index;
    }

    /**
     * Reference to function in Profile.function_table.
     *
     * Generated from protobuf field <code>int32 function_index = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setFunctionIndex($var)
    {
        GPBUtil::checkInt32($var);
        $this->function_index = $var;

        return $this;
    }

    /**
     * Line number in source code.
     *
     * Generated from protobuf field <code>int64 line = 2;</code>
     * @return int|string
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Line number in source code.
     *
     * Generated from protobuf field <code>int64 line = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setLine($var)
    {
        GPBUtil::checkInt64($var);
        $this->line = $var;

        return $this;
    }

    /**
     * Column number in source code.
     *
     * Generated from protobuf field <code>int64 column = 3;</code>
     * @return int|string
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Column number in source code.
     *
     * Generated from protobuf field <code>int64 column = 3;</code>
     * @param int|string $var
     * @return $this
     */
    public function setColumn($var)
    {
        GPBUtil::checkInt64($var);
        $this->column = $var;

        return $this;
    }

}

