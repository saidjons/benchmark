<?php

namespace Ubiquity\contents\validation\validators\comparison;

use Ajax\semantic\components\validation\CustomRule;
use Ajax\semantic\components\validation\Rule;
use Ubiquity\contents\validation\validators\ValidatorHasNotNull;

class LessThanValidator extends ValidatorHasNotNull {
	protected $ref;

	public function __construct() {
		$this->message = 'This value should be smaller than `{ref}`';
	}

	public function validate($value) {
		parent::validate ( $value );
		if ($this->notNull !== false) {
			return $value < $this->ref;
		}
		return true;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Ubiquity\contents\validation\validators\Validator::getParameters()
	 */
	public function getParameters(): array {
		return [ 'ref','value' ];
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Ubiquity\contents\validation\validators\Validator::asUI()
	 */
	public function asUI(): array {
		$rule=new CustomRule('lessthan',"function(v,lessThan){ return v<lessThan;}",$this->_getMessage(),$this->ref);
		return \array_merge_recursive(parent::asUI () , ['rules'=>[$rule]]);
	}
}

