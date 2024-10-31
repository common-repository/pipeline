(function ($) {
    $.fn.repeater = function (options) {
        // Default params
        var params = $.extend({
            structure: [],
            items: [],
            repeatElement: 'structure',
            createElement: 'createElement',
            removeElement: 'removeElement',
            containerElement: 'containerElement',
            groupName: 'group'
        }, options);

        var repeater = this;

        repeater.find('#' + params.createElement).click(function () {
            var cloned = repeater.find('#' + params.repeatElement).clone();
            var next_count = $('#containerElement').find('#field_count').val();
            var next_count_final = parseInt(next_count) + 1;
            var inputs = cloned.find(':input');
            var newItem = [];
            $.each(inputs, function (key, input) {
                var next = params.items.length;
                newItem.push({
                    id: $(input).attr('id'),
                    value: ''
                })
                if(next_count_final == '') {
					next = next;
				} else {
					next = next_count_final;
				}
                console.log('next::'+next);
                $(input).attr('name', params.groupName + '[' + next + '][' + $(input).attr('name') + ']')
            })
            cloned.append('<input type="button" class="' + params.removeElement + '" value="remove" />')
            cloned.find('.' + params.removeElement).click(function () {
                $(this).parent().remove();
                if (params.onRemove !== undefined) {
                    params.onRemove();
                }
            })
            cloned.show()
            cloned.appendTo('#' + params.containerElement)
            if (params.onShow !== undefined) {
                params.onShow();
            }
            params.items.push(newItem)
        });

        if (params.items.length > 0) {
            $.each(params.items, function (key1, item) {
				//~ console.log('key1'+key1);
				//~ console.log('item'+item);
                var cloned = repeater.find('#' + params.repeatElement).clone();
                var next_count = $('#containerElement').find('#field_count').val();
                var inputs = cloned.find(':input');
                $.each(inputs, function (key2, input) {
				    console.log('next3::'+key1);
				    
					if(next_count == '') {
						key1 = key1;
					} else {
						key1 = next_count;
					}
                    $(input).attr('name', params.groupName + '[' + key1 + '][' + $(input).attr('name') + ']')
                })
                cloned.append('<input type="button" class="' + params.removeElement + '" value="remove" />')
                $.each(item.elements, function (index, element) {
                    cloned.find('#' + element.id).val(element.value)
                })
                cloned.find('.' + params.removeElement).click(function () {
                    $(this).parent().remove();
                    if (params.onRemove !== undefined) {
                        params.onRemove();
                    }
                })
                $('.db_fields').find('.' + params.removeElement).click(function () {
                    $(this).parent().remove();
                    if (params.onRemove !== undefined) {
                        params.onRemove();
                    }
                })
                console.log('here');
                cloned.show()
                cloned.appendTo('#' + params.containerElement)
            })
        }

    }
}(jQuery));
