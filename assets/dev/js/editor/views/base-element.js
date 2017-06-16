var BaseSettingsModel = require( 'builder-models/base-settings' ),
	Stylesheet = require( 'builder-utils/stylesheet' ),
	BaseElementView;

BaseElementView = Marionette.CompositeView.extend( {
	tagName: 'div',

	stylesheet: null,

	className: function() {
		return this.getElementUniqueID();
	},

	attributes: function() {
		var type = this.model.get( 'elType' );

		if ( 'widget'  === type ) {
			type = this.model.get( 'widgetType' );
		}
		return {
			'data-element_type': type
		};
	},

	ui: function() {
		return {
			duplicateButton: '> .builder-editor-element-settings .builder-editor-element-duplicate',
			removeButton: '> .builder-editor-element-settings .builder-editor-element-remove',
			saveButton: '> .builder-editor-element-settings .builder-editor-element-save'
		};
	},

	events: function() {
		return {
			'click @ui.removeButton': 'onClickRemove',
			'click @ui.saveButton': 'onClickSave',
			'click @ui.duplicateButton': 'duplicate'
		};
	},

	$stylesheetElement: null,

	getElementType: function() {
		return this.model.get( 'elType' );
	},

	getChildType: function() {
		return builder.helpers.getElementChildType( this.getElementType() );
	},

	getChildView: function( model ) {
		var ChildView,
			elType = model.get( 'elType' );

		if ( 'section' === elType ) {
			ChildView = require( 'builder-views/section' );
		} else if ( 'column' === elType ) {
			ChildView = require( 'builder-views/column' );
		} else {
			ChildView = builder.modules.WidgetView;
		}

		return builder.hooks.applyFilters( 'element/view', ChildView, model, this );
	},

	templateHelpers: function() {
		return {
			elementModel: this.model,
			editModel: this.getEditModel()
		};
	},

	getTemplateType: function() {
		return 'js';
	},

	getEditModel: function() {
		return this.model;
	},

	initialize: function() {
		// grab the child collection from the parent model
		// so that we can render the collection as children
		// of this parent element
		this.collection = this.model.get( 'elements' );

		if ( this.collection ) {
			this.listenTo( this.collection, 'add remove reset', this.onCollectionChanged, this );
			this.listenTo( this.collection, 'switch', this.handleElementHover, this );
		}

		var editModel = this.getEditModel();

		this.listenTo( editModel.get( 'settings' ), 'change', this.onSettingsChanged, this );
		this.listenTo( editModel.get( 'editSettings' ), 'change', this.onSettingsChanged, this );

		this.on( 'render', function() {
			this.renderUI();
			this.runReadyTrigger();
		} );

		this.initRemoveDialog();

		this.initStylesheet();
	},

    handleElementHover: function( ) {

        var self = this;

        var config = {
            class : 'builder-element-settings-active'
        };

        var hoverConfig = {
            sensitivity: 1, // number = sensitivity threshold (must be 1 or higher)
            interval: 10, // number = milliseconds for onMouseOver polling interval
            timeout: 500, // number = milliseconds delay before onMouseOut
            over: function() {
                self.$el.addClass( config.class );
            },
            out: function() {
                self.$el.removeClass(config.class );
            }
        };

        self.$el.hoverIntent(hoverConfig);

    },

	edit: function() {
		builder.getPanelView().openEditor( this.getEditModel(), this );
	},

	addChildModel: function( model, options ) {
		return this.collection.add( model, options, true );
	},

	addChildElement: function( itemData, options ) {
		options = options || {};

		var myChildType = this.getChildType();

		if ( -1 === myChildType.indexOf( itemData.elType ) ) {
			delete options.at;

			return this.children.last().addChildElement( itemData, options );
		}

		var newModel = this.addChildModel( itemData, options ),
			newView = this.children.findByModel( newModel );

		if ( 'section' === newView.getElementType() && newView.isInner() ) {
			newView.addEmptyColumn();
		}

		newView.edit();

		return newView;
	},

	addElementFromPanel: function( options ) {
		var elementView = builder.channels.panelElements.request( 'element:selected' );

		var itemData = {
			id: builder.helpers.getUniqueID(),
			elType: elementView.model.get( 'elType' )
		};

		if ( 'widget' === itemData.elType ) {
			itemData.widgetType = elementView.model.get( 'widgetType' );
		} else if ( 'section' === itemData.elType ) {
			itemData.elements = [];
			itemData.isInner = true;
		} else {
			return;
		}

		var customData = elementView.model.get( 'custom' );

		if ( customData ) {
			_.extend( itemData, customData );
		}

		this.addChildElement( itemData, options );
	},

	isCollectionFilled: function() {
		return false;
	},

	isInner: function() {
		return !! this.model.get( 'isInner' );
	},

	initRemoveDialog: function() {
		var removeDialog;

		this.getRemoveDialog = function() {
			if ( ! removeDialog ) {
				var elementTitle = this.model.getTitle();

				removeDialog = builder.dialogsManager.createWidget( 'confirm', {
					message: builder.translate( 'dialog_confirm_delete', [ elementTitle.toLowerCase() ] ),
					headerMessage: builder.translate( 'delete_element', [ elementTitle ] ),
					strings: {
						confirm: builder.translate( 'delete' ),
						cancel: builder.translate( 'cancel' )
					},
					defaultOption: 'confirm',
					onConfirm: _.bind( function() {
						this.model.destroy();
					}, this )
				} );
			}

			return removeDialog;
		};
	},

	initStylesheet: function() {
		var viewportBreakpoints = builder.config.viewportBreakpoints;

		this.stylesheet = new Stylesheet();

		this.stylesheet
			.addDevice( 'mobile', 0 )
			.addDevice( 'tablet', viewportBreakpoints.md )
			.addDevice( 'desktop', viewportBreakpoints.lg );
	},

	createStylesheetElement: function() {
		this.$stylesheetElement = Backbone.$( '<style>', { id: 'builder-style-' + this.model.cid } );

		builder.$previewContents.find( 'head' ).append( this.$stylesheetElement );
	},

	enqueueFonts: function() {
		var editModel = this.getEditModel(),
			settings = editModel.get( 'settings' );

		_.each( settings.getFontControls(), _.bind( function( control ) {
			var fontFamilyName = editModel.getSetting( control.name );

			if ( _.isEmpty( fontFamilyName ) ) {
				return;
			}

			builder.helpers.enqueueFont( fontFamilyName );
		}, this ) );
	},

	addStyleRules: function( controls, values, placeholders, replacements ) {
		var self = this;

		_.each( controls, function( control ) {
			if ( control.styleFields ) {
				values[ control.name ].each( function( itemModel ) {
					self.addStyleRules(
						control.styleFields,
						itemModel.attributes,
						placeholders.concat( [ '{{CURRENT_ITEM}}' ] ),
						replacements.concat( [ '.builder-repeater-item-' + itemModel.get( '_id' ) ] )
					);
				} );
			}

			self.addControlStyleRules( control, values, self.getEditModel().get( 'settings' ).controls, placeholders, replacements );
		} );
	},

	addControlStyleRules: function( control, values, controlsStack, placeholders, replacements ) {
		var self = this;

		BaseElementView.addControlStyleRules( self.stylesheet, control, controlsStack, function( control ) {
			return self.getStyleControlValue( control, values );
		}, placeholders, replacements );
	},

	addStyleToDocument: function() {
		var styleText = this.stylesheet.toString();

		styleText = builder.hooks.applyFilters( 'editor/style/styleText', styleText, this );

		if ( _.isEmpty( styleText ) && ! this.$stylesheetElement ) {
			return;
		}

		if ( ! this.$stylesheetElement ) {
			this.createStylesheetElement();
		}

		this.$stylesheetElement.text( styleText );
	},

	getStyleControlValue: function( control, values ) {
		var value = values[ control.name ];

		if ( control.selectors_dictionary ) {
			value = control.selectors_dictionary[ value ] || value;
		}

		if ( ! _.isNumber( value ) && _.isEmpty( value ) ) {
			return;
		}

		return value;
	},

	renderStyles: function() {
		var self = this,
			settings = self.getEditModel().get( 'settings' );

		self.stylesheet.empty();

		self.addStyleRules( settings.getStyleControls(), settings.attributes, [ /\{\{WRAPPER}}/g ], [ '#builder .' + self.getElementUniqueID() ] );

		if ( 'column' === self.model.get( 'elType' ) ) {
			var inlineSize = settings.get( '_inline_size' );

			if ( ! _.isEmpty( inlineSize ) ) {
				self.stylesheet.addRules( '#builder .' + self.getElementUniqueID(), { width: inlineSize + '%' }, { min: 'tablet' } );
			}
		}

		self.addStyleToDocument();
	},

	renderCustomClasses: function() {

		var self = this;

		self.$el.addClass( 'builder-element' );

		var settings = self.getEditModel().get( 'settings' );

		_.each( settings.attributes, function( value, attribute ) {
			if ( settings.isClassControl( attribute ) ) {
				var currentControl = settings.getControl( attribute ),
					previousClassValue = settings.previous( attribute ),
					classValue = value;

				if ( currentControl.classes_dictionary ) {
					if ( undefined !== currentControl.classes_dictionary[ previousClassValue ] ) {
						previousClassValue = currentControl.classes_dictionary[ previousClassValue ];
					}

					if ( undefined !== currentControl.classes_dictionary[ value ] ) {
						classValue = currentControl.classes_dictionary[ value ];
					}
				}

				self.$el.removeClass( currentControl.prefix_class + previousClassValue );

				var isVisible = builder.helpers.isActiveControl( currentControl, settings.attributes );

				if ( isVisible && ! _.isEmpty( classValue ) ) {
					self.$el
						.addClass( currentControl.prefix_class + classValue )
						.addClass( _.result( self, 'className' ) );
				}
			}
		} );
	},

	renderUI: function() {
		this.renderStyles();
		this.renderCustomClasses();
		this.enqueueFonts();
	},

	runReadyTrigger: function() {
		_.defer( _.bind( function() {
			builderFrontend.elementsHandler.runReadyTrigger( this.$el );
		}, this ) );
	},

	getElementUniqueID: function() {
		return 'builder-element-' + this.model.get( 'id' );
	},

	duplicate: function( event ) {
		event.preventDefault();
		this.trigger( 'request:duplicate' );
	},

	confirmRemove: function() {
		this.getRemoveDialog().show();
	},

	onClickEdit: function( event ) {
		event.preventDefault();
		event.stopPropagation();

		var activeMode = builder.channels.dataEditMode.request( 'activeMode' );

		if ( 'edit' !== activeMode ) {
			return;
		}

		this.edit();
	},

	onCollectionChanged: function() {
		builder.setFlagEditorChange( true );
	},

	onSettingsChanged: function( settings ) {
		var editModel = this.getEditModel();

		if ( editModel.get( 'editSettings' ) !== settings ) {
			// Change flag only if server settings was changed
			builder.setFlagEditorChange( true );
		}

		// Make sure is correct model
		if ( settings instanceof BaseSettingsModel ) {
			var isContentChanged = false;

			_.each( settings.changedAttributes(), function( settingValue, settingKey ) {
				var control = settings.getControl( settingKey );

				if ( ! control ) {
					return;
				}

				if ( control.force_render || ! settings.isStyleControl( settingKey ) && ! settings.isClassControl( settingKey ) ) {
					isContentChanged = true;
				}
			} );

			if ( ! isContentChanged ) {
				this.renderUI();
				return;
			}
		}

		// Re-render the template
		var templateType = this.getTemplateType();

		if ( 'js' === templateType ) {
			this.getEditModel().setHtmlCache();
			this.render();
			editModel.renderOnLeave = true;
		} else {
			editModel.renderRemoteServer();
		}
	},

	onClickRemove: function( event ) {
		event.preventDefault();
		event.stopPropagation();

		this.confirmRemove();
	},

	onClickSave: function( event ) {
		event.preventDefault();

		var model = this.model;

		builder.templates.startModal( function() {
			builder.templates.getLayout().showSaveTemplateView( model );
		} );
	},

	onDestroy: function() {
		if ( this.$stylesheetElement ) {
			this.$stylesheetElement.remove();
		}
	}
}, {
	addControlStyleRules: function( stylesheet, control, controlsStack, valueCallback, placeholders, replacements ) {
		var value = valueCallback( control );

		if ( undefined === value ) {
			return;
		}

		_.each( control.selectors, function( cssProperty, selector ) {
			var outputCssProperty;

			try {
				outputCssProperty = cssProperty.replace( /\{\{(?:([^.}]+)\.)?([^}]*)}}/g, function( originalPhrase, controlName, placeholder ) {
					var parserControl = control,
						valueToInsert = value;

					if ( controlName ) {
						parserControl = _.findWhere( controlsStack, { name: controlName } );

						valueToInsert = valueCallback( parserControl );
					}

					var parsedValue = builder.getControlItemView( parserControl.type ).getStyleValue( placeholder.toLowerCase(), valueToInsert );

					if ( '' === parsedValue ) {
						throw '';
					}

					return parsedValue;
				} );
			} catch ( e ) {
				return;
			}

			if ( _.isEmpty( outputCssProperty ) ) {
				return;
			}

			var devicePattern = /^\(([^)]+)\)/,
				deviceRule = selector.match( devicePattern );

			if ( deviceRule ) {
				selector = selector.replace( devicePattern, '' );

				deviceRule = deviceRule[1];
			}

			_.each( placeholders, function( placeholder, index ) {
				selector = selector.replace( placeholder, replacements[ index ] );
			} );

			var device = deviceRule,
				query;

			if ( ! device && control.responsive ) {
				device = control.responsive;
			}

			if ( device && 'desktop' !== device ) {
				query = { max: device };
			}

			stylesheet.addRules( selector, outputCssProperty, query );
		} );
	}
} );

module.exports = BaseElementView;
