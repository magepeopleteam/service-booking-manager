(function(blocks, element, components, editor) {
    var el = element.createElement;
    var SelectControl = components.SelectControl;
    var useSelect = wp.data.useSelect;
    var ServerSideRender = wp.serverSideRender;

    blocks.registerBlockType('mpwpb/service-booking', {
        title: 'Service Booking',
        icon: 'calendar-alt',
        category: 'common',
        attributes: {
            post_id: {
                type: 'string',
                default: ''
            }
        },
        
        edit: function(props) {
            var services = useSelect(function(select) {
                return select('core').getEntityRecords('postType', 'mpwpb_item', {
                    per_page: -1,
                    status: 'publish'
                });
            }, []);

            // Create options array for the select control
            var options = [{
                label: 'Select a Service',
                value: ''
            }];
            
            if (services) {
                services.forEach(function(service) {
                    options.push({
                        label: service.title.rendered,
                        value: service.id.toString()
                    });
                });
            }

            return el(
                'div',
                { className: props.className },
                [
                    el('div', { className: 'components-placeholder' },
                        [
                            el('div', { className: 'components-placeholder__label' },
                                'Service Booking'
                            ),
                            el(SelectControl, {
                                label: 'Select Service',
                                value: props.attributes.post_id,
                                options: options,
                                onChange: function(value) {
                                    props.setAttributes({ post_id: value });
                                }
                            })
                        ]
                    ),
                    props.attributes.post_id && el(
                        'div',
                        { className: 'mpwpb-preview-wrapper' },
                        el(ServerSideRender, {
                            block: 'mpwpb/service-booking',
                            attributes: props.attributes
                        })
                    )
                ]
            );
        },

        save: function() {
            return null; // Dynamic block, render callback on PHP side
        }
    });
}(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.editor
)); 