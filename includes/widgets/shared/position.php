<?php
namespace Qazana;

add_action( 'qazana/element/after_section_end', 'Qazana\add_position_options_to_widget', 10, 3);
/**
 * Position controls
 */
function add_position_options_to_widget($element, $section_id, $args) {

    if ( $section_id == 'section_advanced' || '_section_style' === $section_id ) {

        $element->start_controls_section(
            '_section_element_position',
            [
                'label' => esc_html__( 'Position', 'qazana' ),
                'tab' => Controls_Manager::TAB_ADVANCED,
            ]
        );

        $element->add_responsive_control(
            'element_position',
            [
                'type' => Controls_Manager::SELECT,
                'label' => esc_html__( 'Position', 'qazana' ),
                 'default' => '',
                 'options' => [
                    ''  => esc_html__( 'Default', 'qazana' ),
                    'static'  => esc_html__( 'Static', 'qazana' ),
                    'relative' => esc_html__( 'Relative', 'qazana' ),
                    'absolute' => esc_html__( 'Absolute', 'qazana' ),
                    'fixed' => esc_html__( 'Fixed', 'qazana' ),
                 ],
                 'selectors' => [ // You can use the selected value in an auto-generated css rule.
                    '{{WRAPPER}}' => 'position: {{VALUE}}',
                 ],
            ]
        );

        $element->add_responsive_control(
            'element_left',
            [
                'label' => esc_html__( 'Left', 'qazana' ),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 0,
                ],
                'range' => [
                    'px' => [
                        'min' => -1000,
                        'max' => 1000,
                        'step' => 1,
                    ],
                    '%' => [
                        'min' => -100,
                        'max' => 100,
                    ],
                ],
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}}' => 'left: {{SIZE}}{{UNIT}} !important;',
                ],
                'condition' => [
                    'element_position!' => array('','static'),
                ],
            ]
        );
        $element->add_responsive_control(
            'element_top',
            [
                'label' => esc_html__( 'Top', 'qazana' ),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 0,
                ],
                'range' => [
                    'px' => [
                        'min' => -1000,
                        'max' => 1000,
                        'step' => 1,
                    ],
                    '%' => [
                        'min' => -100,
                        'max' => 100,
                    ],
                ],
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}}' => 'top: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'element_position!' => array('','static'),
                ],
            ]
        );

        $element->add_control(
            'element_rotate',
            [
                'label' => esc_html__( 'Rotate Item', 'qazana' ),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'label_on' => esc_html__( 'On', 'qazana' ),
                'label_off' => esc_html__( 'Off', 'qazana' ),
                'return_value' => 'yes',
            ]
        );

        $element->add_control(
            'element_rotate_degrees',
            [
                'label' => esc_html__( 'Rotate', 'qazana' ),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 0,
                ],
                'range' => [
                    'px' => [
                        'min' => -180,
                        'max' => 180,
                    ],
                ],
                'size_units' => [ 'px' ],
                'selectors' => [
                    '{{WRAPPER}}' => 'transform: rotate({{SIZE}}deg);',
                ],
                'condition' => [
                    'element_rotate' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'element_transform_origin',
            [
                'label' => esc_html__( 'Transform Origin', 'qazana' ),
                'type' => Controls_Manager::TEXT,
                'default' => '50% 50%',
                'selectors' => [
                    '{{WRAPPER}}' => 'transform-origin: {{VALUE}};',
                ],
                'condition' => [
                    'element_rotate' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'element_overflow',
            [
                'label' => esc_html__( 'Overflow', 'qazana' ),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                   ''  => esc_html__( 'Default', 'qazana' ),
                   'hidden' => esc_html__( 'Hidden', 'qazana' ),
                   'visible' => esc_html__( 'Visible', 'qazana' ),
                ],
                'selectors' => [
                    '{{WRAPPER}}' => 'overflow: {{VALUE}};',
                ],
            ]
        );

        $element->end_controls_section();
    }
}