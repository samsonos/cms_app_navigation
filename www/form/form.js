/**
 * Created by p.onysko on 03.03.14.
 */
var loader = new Loader(s('body'));

function CMSNavigationFormInit(tree) {
    tree.treeview(
        true,
        function(tree) {
            CMSNavigationFormInit(tree);
        }
    );

    // Флаг нажатия на кнопку управления
    var ControlFormOpened = false;

    // Указатель на текущий набор кнопок управления
    var ControlElement = null;

    var parent;
    /**
    * обработчик добавления новой записи
    */
    s(".control.add", tree).tinyboxAjax({
        html:'html',
        renderedHandler: function(response, tb) {
            /** автоматический транслит Урл*/
            s("#Name").keyup(function(obj) {
                s("#Url").val(s("#Name").translit());
            });
            /** транслит по кнопке */
            s("#generateUrl").click(function(obj) {
                if (confirm("Вы точно хотите сгенерировать адрес?")) {
                    s("#Url").val(s("#Name").translit());
                }
            });
            s(".form2").ajaxSubmit(function(response) {
                s('.sub_menu').html(response.sub_menu);
                parent.html(response.tree);
                CMSNavigationFormInit(parent);
                AppNavigationInitSubMenu();
                tb.close();
            }, function(form) {
                s('input[type="submit"]', form).a('disabled', 'disabled');
                return true;
            });
            s(".cancel-button").click(function() {
                tb.close();
            });
            //CMSNavigationFormInit();
        },
        beforeHandler: function(link) {
            parent = link.parent(' sjs-treeview');
            loader.show('Загрузка формы', true);
            return true;
        },
        responseHandler: function() {
            loader.hide();
            return true;
        }
    });
    /**
     * обработчик редактирование новой записи
     */
    s(".control.editstr", tree).tinyboxAjax({
        html:'html',
        renderedHandler: function(response, tb) {
            s("#generateUrl").click(function(obj) {
                if (confirm("Вы точно хотите сгенерировать адрес?")) {
                    s("#Url").val(s("#Name").translit());
                }
            });
            s(".form2").ajaxSubmit(function(response) {
                s('.sub_menu').html(response.sub_menu);
                parent.html(response.tree);
                CMSNavigationFormInit(parent);
                AppNavigationInitSubMenu();
                tb.close();
            });
            s(".cancel-button").click(function() {
                tb.close();
            });
        },
        beforeHandler: function(link) {
            parent = link.parent(' sjs-treeview');
            loader.show('Загрузка формы', true);
            return true;
        },
        responseHandler: function() {
            loader.hide();
            return true;
        }
    });
    /**
     * обработка удаления
     */
    s(".control.delete", tree).ajaxClick(function(response) {
        parent.html(response.tree);
        CMSNavigationFormInit(parent);
        s('.sub_menu').html(response.sub_menu);
        AppNavigationInitSubMenu();
        loader.hide();
    }, function(link) {
        parent = link.parent(' sjs-treeview');
        if (confirm("Вы уверены, что хотите безвозвратно удалить структуру?")) {
            loader.show('Удаление структуры', true);
            return true;
        } else {
            return false;
        }
    });

    s('.control.fields', tree).tinyboxAjax({
        html : 'html',
        renderedHandler: function(response, tb){
            fieldForm(response);
        },
        beforeHandler: function() {
            loader.show('Загрузка формы', true);
            return true;
        },
        responseHandler: function() {
            loader.hide();
            return true;
        }
    });

    /**
     * обработка изменения позиции элемента в дереве
     */
    s(".control.move-up", tree).ajaxClick(function(response) {
        s(".tree-container").html(response.tree);
        CMSNavigationFormInit(s(".tree-container"));
        s('.sub_menu').html(response.sub_menu);
        AppNavigationInitSubMenu();
        loader.hide();
    }, function() {
        loader.show('Обновление дерева', true);
        return true;
    });
    s(".control.move-down", tree).ajaxClick(function(response) {
        s(".tree-container").html(response.tree);
        CMSNavigationFormInit(s(".tree-container"));
        s('.sub_menu').html(response.sub_menu);
        AppNavigationInitSubMenu();
        s( '.structure-element' )
            .mouseover( function(el){ if(!ControlFormOpened) { s( '.control-buttons', el ).show(); ControlElement = el; } })
            .mouseout( 	function(el){ if(!ControlFormOpened) s( '.control-buttons', el ).hide(); });
        loader.hide();
    }, function() {
        loader.show('Обновление дерева', true);
        return true;
    });

    s('.open', s('.noChildren', tree)).click(function() {
        return false;
    });
    s(".open", s('.hasChildren', tree)).ajaxClick(function(response) {
        s("#data").html(response.tree);
        CMSNavigationFormInit(s("#data"));
        s('.sub_menu').html(response.sub_menu);
        AppNavigationInitSubMenu();
        s(".all").removeClass('active');

        loader.hide();
    }, function() {
        loader.show('Открытие структуры', true);
        return true;
    });
}

function AppNavigationInitSubMenu() {
    /**
     * обработчик для кнопки "верхнего" меню (sub_menu)
     */
    s("#newSSE").tinyboxAjax({
        html:'html',
        renderedHandler: function(response, tb) {
            /** автоматический транслит Урл*/
            s("#Name").keyup(function(obj) {
                s("#Url").val(s("#Name").translit());
            });
            /** транслит по кнопке */
            s("#generateUrl").click(function(obj) {
                if (confirm("Вы точно хотите сгенерировать адрес?")) {
                    s("#Url").val(s("#Name").translit());
                }
            });
            s(".form2").ajaxSubmit(function(response) {
                s(".tree-container").html(response.tree);
                CMSNavigationFormInit(s(".tree-container"));
                tb.close();
            }, function(form) {
                s('input[type="submit"]', form).a('disabled', 'disabled');
                return true;
            });
            s(".cancel-button").click(function() {
                tb.close();
            });
        },
        beforeHandler: function() {
            loader.show('Загрузка формы', true);
            return true;
        },
        responseHandler: function() {
            loader.hide();
            return true;
        }
    });

    s(".all").ajaxClick(function(response) {
        s('.sub_menu').html(response.sub_menu);
        s("#data").html(response.tree);
        CMSNavigationFormInit(s('#data'));
        AppNavigationInitSubMenu();
        s(".all").addClass('active');

        loader.hide();
    }, function() {
        loader.show('Открытие структуры', true);
        return true;
    });
}

s('#structure').pageInit(function() {
    AppNavigationInitSubMenu();

    CMSNavigationFormInit(s(".tree-container")); //инициализация событий
});
