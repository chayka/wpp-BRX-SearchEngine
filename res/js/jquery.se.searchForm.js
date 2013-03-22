(function( $ ) {
//    $.widgetTemplated( "brx.spinner", $.ui.templated, {
    $.widget( "se.searchForm", $.ui.templated, {
 
//        _parentPrototype: $.ui.templated.prototype,
        
        // These options will be used as defaults
        options: { 
            elementAsTemplate: true
        },
        
        postCreate: function(){
            console.dir({'se.searchForm': this});
        },
        
        sampleClicked: function(){
            var sample = this.get('sampleView').text();
            this.get('inputQuery').val(sample);
        },
        
        scopeChanged: function(){
            var scope = this.get('boxScopeOptions').find('input:radio:checked').val();
            console.info('scope is: '+scope);
            scope = 'all'==scope?'':scope+'/';
            this.get('form').attr('action', '/search/'+scope);
        }
    });
}(jQuery))