( function( blocks, element, components, serverSideRender ) {
    var el = element.createElement;
    var SelectControl = components.SelectControl;
    var __ = wp.i18n.__;

    blocks.registerBlockType( 'service-booking-manager/booking-form', {
        title: __( 'Service Booking Form', 'service-booking-manager' ),
        icon: 'calendar-alt',
        category: 'widgets',
        attributes: {
            post_id: {
                type: 'string',
                default: ''
            }
        },
        
        edit: function( props ) {
            var post_id = props.attributes.post_id;
            
            // Function to handle form selection change
            function onChangeForm( newFormId ) {
                props.setAttributes( { post_id: newFormId } );
            }
            
            return (
                el( 'div', { className: 'mpwpb-block-wrapper' },
                    el( SelectControl, {
                        label: __( 'Select Booking Form', 'service-booking-manager' ),
                        value: post_id,
                        options: mpwpbBlockData.forms,
                        onChange: onChangeForm
                    } ),
                    !post_id && el( 'p', { className: 'mpwpb-notice' },
                        __( 'Please select a booking form to display.', 'service-booking-manager' )
                    ),
                    post_id && el( serverSideRender, {
                        block: 'service-booking-manager/booking-form',
                        attributes: props.attributes
                    } )
                )
            );
        },

        save: function() {
            return null; // Dynamic block, render handled by PHP
        }
    } );
} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.serverSideRender
); 