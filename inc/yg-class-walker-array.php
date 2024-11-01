<?php
class YG_Walker_Array {
	public function walk( $elements, $max_depth = 0 ) {
		$args = array_slice(func_get_args(), 2);
		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}
		$output = '';
		if ( $max_depth < -1 || empty( $elements ) ) {
			return $output;
		}
		
		foreach ( $elements as $e) {
			if(is_array($e))
				$this->display_element( $e, 0, $args, $output, null, null );
		}
		$output = substr($output, 0, -4);
		return $output;
	}
	public function display_element( $element, $depth, $args, &$output, $operator, $blockoperator ) {
		if ( ! $element ) {
			return;
		}

		if($isBlock = $this->hasChildren( $element )){//is block
			if($blockoperator==null)
				$blockoperator = ($element['val']=='or')?' || ':' && ';
			$operator = ($element['val']=='or')?' || ':' && ';
			$output .= '(';
			foreach ($isBlock as $conLine) {
				$this->display_element( $conLine, $depth+1, $args, $output, $operator, $blockoperator );
			}
			$output = substr($output, 0, -4);
			$output .= ')'.$blockoperator;
		}else{//is cond
			if(isset($args['defenition']) && isset($element['type']) && isset($element['desc']) && isset($args['defenition'][$element['type']][$element['desc']]['js'])){
				$js = $args['defenition'][$element['type']][$element['desc']]['js'];
				foreach ($element as $key=>$val) {
					if($key!='type' && $key!='desc')
						$js = str_replace('{{'.$key.'}}',strtolower($val),$js);
				}
				$output .= '('.$js.')'.$operator;
			}
		}
	}
	public function hasChildren( $element ) {
		$has = false;
		if(isset($element['type']) && $element['type']=='operator'){
			unset($element['type']);
			unset($element['val']);
			if(!empty($element))
				$has = $element;
		}
		return $has;
	}
}
