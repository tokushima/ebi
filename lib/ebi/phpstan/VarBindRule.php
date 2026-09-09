<?php
namespace ebi\phpstan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * #[\ebi\Attribute\VarBind] の prop / via が、対象クラスの実在プロパティ
 * （trait / 親 / 自身）を指しているかを静的に検証する PHPStan ルール。
 * タイポや存在しないプロパティを編集時 / CI で検出する（実行時コストは無し）。
 *
 * 実行時の \ebi\AttributeReader は検証しない設計。開発時のみ本ルールで担保する。
 *
 * @implements Rule<Class_>
 */
class VarBindRule implements Rule{
	public function __construct(private ReflectionProvider $reflectionProvider){}

	public function getNodeType(): string{
		return Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array{
		if(!($node instanceof Class_) || $node->namespacedName === null){
			return [];
		}
		$className = (string)$node->namespacedName;
		if(!$this->reflectionProvider->hasClass($className)){
			return [];
		}
		$class = $this->reflectionProvider->getClass($className);

		$errors = [];
		foreach($node->attrGroups as $group){
			foreach($group->attrs as $attr){
				if(ltrim($attr->name->toString(), '\\') !== 'ebi\\Attribute\\VarBind'){
					continue;
				}
				[$prop, $via] = $this->extract_prop_via($attr->args);
				if($prop !== null && !$class->hasProperty($prop)){
					$errors[] = RuleErrorBuilder::message(
						sprintf('#[VarBind] prop "%s" は %s に存在しないプロパティです。', $prop, $className)
					)->identifier('ebi.varBind.prop')->line($attr->getStartLine())->build();
				}
				if($via !== null && !$class->hasProperty($via)){
					$errors[] = RuleErrorBuilder::message(
						sprintf('#[VarBind(prop: %s)] via "%s" は %s に存在しないプロパティです。', (string)$prop, $via, $className)
					)->identifier('ebi.varBind.via')->line($attr->getStartLine())->build();
				}
			}
		}
		return $errors;
	}

	/**
	 * VarBind 属性の引数から prop / via の文字列リテラルを取り出す（名前付き / 第1引数の位置指定に対応）。
	 * @param \PhpParser\Node\Arg[] $args
	 * @return array{0: ?string, 1: ?string}
	 */
	private function extract_prop_via(array $args): array{
		$prop = null;
		$via = null;
		$pos = 0;
		foreach($args as $arg){
			$name = $arg->name?->toString();
			$value = ($arg->value instanceof Node\Scalar\String_) ? $arg->value->value : null;
			if($name === 'prop' || ($name === null && $pos === 0)){
				$prop = $value;
			}elseif($name === 'via'){
				$via = $value;
			}
			$pos++;
		}
		return [$prop, $via];
	}
}
