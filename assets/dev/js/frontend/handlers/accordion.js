var TabsModule = require( 'qazana-frontend/handlers/base-tabs' );

module.exports = function( $scope ) {
	new TabsModule( {
		$element: $scope,
		showTabFn: 'slideDown',
		hideTabFn: 'slideUp',
	} );
};
