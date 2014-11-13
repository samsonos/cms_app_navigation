function StructureTree()
{
	// Флаг нажатия на кнопку управления
	var ControlFormOpened = false;

	// Указатель на текущий набор кнопок управления
	var ControlElement = null;

	/**
	 * Инициализировать дерево ЭСС
	 */
	var init = function( html )
	{
		// Если передано HTML - заполним дерево
		if( html && html.length ) s( '.tree-container' ).html( html );



	};

	// Инициализируем дерево ЭСС
	init();
}

/**
 * Инициализация модуля ЭСС
 */
s('#structure').pageInit( StructureTree );