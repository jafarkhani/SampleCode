<?php
//-------------------------
// Developer:	Jafarkhani
// Create Date:	2016.09
//-------------------------

namespace Core;

class TreeModulesclass
{
	/**
	 * 
	 * @param array $dataTable : this array should have at least id,parentid,text and id should be unique
	 */
	static function MakeHierarchyArray($dataTable, 
			$parentFieldName = "ParentID", $idFieldName = "id", $textFieldName = "text",
			$ignoreErrors = false)
	{
		$nodes = array();
		$refArr = array();
		for($i=0; $i<count($dataTable); $i++)
		{
			$node = $dataTable[$i];
			$node["leaf"] = "true";
			$node["level"] = 1;
			$node["id"] = $node[$idFieldName];
			$node["text"] = $node[$textFieldName];
			$node["parentId"] = $node[$parentFieldName];
			//------------------------------------------------------------------
			
			if($node["parentId"] == "0")
			{
				$nodes[] = $node;
				$refArr[$node["id"]] = & $nodes[ count($nodes)-1 ];				
			}
			else
			{
				$parent = & $refArr[ $node["parentId"] ];
				if(!$parent)
				{
					ExceptionHandler::PushException("پدر گره با کد " . $node["id"] . " یافت نشد.");
					if(!$ignoreErrors)
						return false;
					else
						continue;
				}
				if (!isset($parent["children"])) {
					$parent["children"] = array();
					$parent["leaf"] = "false";
				}
				
				$node["level"] = $parent["level"]*1+1;
				$parent["children"][] = $node;
				$refArr[$node["id"]] = & $parent["children"][ count($parent["children"])-1 ];
			}
		}
		return $nodes;
	}
}
?>
