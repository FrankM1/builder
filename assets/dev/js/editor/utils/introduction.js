var Introduction;

Introduction = function() {
	var self = this,
		modal;

	var initModal = function() {
		modal = builder.dialogsManager.createWidget( 'builder-modal', {
			id: 'builder-introduction'
		} );

		modal.getElements( 'closeButton' ).on( 'click', function() {
			self.setIntroductionViewed();
		} );

		modal.on( 'hide', function() {
			modal.getElements( 'message' ).empty(); // In order to stop the video
		} );
	};

	this.getSettings = function() {
		return builder.config.introduction;
	};

	this.getModal = function() {
		if ( ! modal ) {
			initModal();
		}

		return modal;
	};

	this.startIntroduction = function() {
		var settings = this.getSettings();

		this.getModal()
		    .setHeaderMessage( settings.title )
		    .setMessage( settings.content )
		    .show();
	};

	this.startOnLoadIntroduction = function() {
		var settings = this.getSettings();

		if ( ! settings.is_user_should_view ) {
			return;
		}

		setTimeout( _.bind( function() {
			this.startIntroduction();
		}, this ), settings.delay );
	};

	this.setIntroductionViewed = function() {
		builder.ajax.send( 'introduction_viewed' );
	};
};

module.exports = new Introduction();