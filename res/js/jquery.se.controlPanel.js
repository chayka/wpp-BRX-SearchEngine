(function($){
    $.widget( "se.controlPanel", $.brx.form, {
 
        // These options will be used as defaults
        options: { 
            elementAsTemplate: true,
            totalToProcess: 0,
            itemsPerIteration: 10,
            operation: {}
        },
        
        postCreate: function(){
            this.set('postTypeInfo', window.postTypeInfo);
            console.dir({'se.controlPanel': this});
            this.render();
            this.element.show('fade', 1000);
//            this.set('totalToProcess', Math.floor(Math.random()*1000))
//            this.setProgress(-1);
//            setInterval($.proxy(function(){
//                var total = this.get('totalToProcess');
//                var processed = Math.floor(Math.random()*total);
//                this.setProgress(processed, total);
//            }, this), 1000);
//            this.get('progressSpinner').show('Выполняется операция');
        },
        
        getSwitch: function(postType){
            return this.get('searchEnabled.'+postType);
        },
        
        getTotalView: function(postType){
            return this.get('cellTotal.'+postType);
        },
        
        getIndexedView: function(postType){
            return this.get('cellIndexed.'+postType);
        },
        
        getLinkCreateIndex: function(postType){
            return this.get('linkCreateIndex.'+postType);
        },
        
        getLinkUpdateIndex: function(postType){
            return this.get('linkUpdateIndex.'+postType);
        },
        
        getLinkDeleteIndex: function(postType){
            return this.get('linkDeleteIndex.'+postType);
        },
        
        isOperationInProgress: function(){
            var operationInProgress = this.get('operation');
            operationInProgress = operationInProgress 
                && $.brx.utils.getItem(operationInProgress, 'command')
                && !$.brx.utils.getItem(operationInProgress, 'stop');
            return operationInProgress;
        },
        
        render: function(){
            var info = this.get('postTypeInfo', {});
            var enabled = [];
            var selectedTotal = 0;
            var selectedIndexed = 0;
            var operationInProgress = this.isOperationInProgress();
            for(var postType in info){
                this.getSwitch(postType)
                    .unbind('change')
                    .val(info[postType].enabled?1:0)
                    .change($.proxy(this.searchEnabledChanged, this, postType));
                    
                if(operationInProgress){
                    this.getSwitch(postType).attr('disabled', 'disabled');
                }else{
                    this.getSwitch(postType).removeAttr('disabled');
                }
                
                this.getTotalView(postType)
                    .text(info[postType].total);
                    
                this.getIndexedView(postType)
                    .text(info[postType].indexed);

                this.getLinkCreateIndex(postType)
                    .unbind('click')
                    .click($.proxy(this.confirmCommand, this, 'create-index', [postType]));

                this.getLinkUpdateIndex(postType)
                    .unbind('click')
                    .click($.proxy(this.confirmCommand, this, 'update-index', [postType]));

                this.getLinkDeleteIndex(postType)
                    .unbind('click')
                    .click($.proxy(this.confirmCommand, this, 'delete-index', [postType]));

                if(info[postType].enabled){
                    enabled.push(postType);
                    selectedTotal+=info[postType].total;
                    selectedIndexed+=info[postType].indexed;
                }
            }
                    
            this.getTotalView('selected')
                .text(selectedTotal);

            this.getIndexedView('selected')
                .text(selectedIndexed);

            this.getLinkCreateIndex('selected')
                .unbind('click')
                .click($.proxy(this.confirmCommand, this, 'create-index', enabled));

            this.getLinkUpdateIndex('selected')
                .unbind('click')
                .click($.proxy(this.confirmCommand, this, 'update-index', enabled));

            this.getLinkDeleteIndex('selected')
                .unbind('click')
                .click($.proxy(this.confirmCommand, this, 'delete-index', enabled));
            
            if(operationInProgress){
                this.element.find('td.actions span').addClass('disabled');
                var op = this.get('operation');
                for(var i in op.postTypes){
                    var postType = op.postTypes[i];
                    switch(op.command){
                        case 'create-index':
                            this.getLinkCreateIndex(postType)
                                .removeClass('disabled')
                                .addClass('selected');
                            break;
                        case 'update-index':
                            this.getLinkUpdateIndex(postType)
                                .removeClass('disabled')
                                .addClass('selected');
                            break;
                    }
                }
            }else{
                this.element.find('td.actions span').removeClass('disabled selected');
                
            }

        },
        
        setProgress: function(processed, total){
            if(processed < 0){
                this.get('progresslabel').text('Подключение...');
                this.get('progressbar').value(false);
            }else if(total){
                var value = Math.floor(processed / total * 100);
                var text = processed + ' / ' + total + ' (' + value + '%)';
                this.get('progresslabel').text(text);
                this.get('progressbar').value(value);
            }else{
                this.get('progresslabel').text('Операция завершена');
                this.get('progressbar').value(100);
            }
            
        },
        
        addLogMessage: function(message){
            var box = $('<div class="message"></div').text(message);
            this.get('boxOutput').prepend(box);
        },
        
        clearLog: function(){
            this.get('boxOutput').empty();
        },
        
        showProgressBox: function(clear){
//            clear = clear || false
            if(clear){
                this.setProgress(-1);
                this.clearLog();
            }
            this.get('boxProgress').show('fade', 1000);
        },
        
        hideProgressBox: function(){
            this.get('boxProgress').hide('fade', 500);
        },
        
        searchEnabledChanged: function(postType){
            console.log('search for '+postType+' is now '+(parseInt(this.getSwitch(postType).val())?'enabled':'disabled'));
                        this.clearMessage();
            this.showSpinner('Обновление данных...');
            var url = parseInt(this.getSwitch(postType).val())?'/api/indexer/enable-type':'/api/indexer/disable-type';
            $.ajax(url, {
                data:{
                    postType: postType,
                },
                dataType: 'json',
                type: 'post'
            })

            .done($.proxy(function(data){
                console.dir({'data': data});
                if(0 == data.code){
                    for(var postType in this.get('postTypeInfo')){
                        this.options.postTypeInfo[postType].enabled = 
                            ($.inArray(postType, data.payload)>=0);
                    }
                    this.render();
                    this.clearMessage();
                }else{
//                        this.processErrors(data.message);
                      this.handleAjaxErrors(data);
                }
            },this))

            .fail($.proxy(function(response){
                var message = $.brx.utils.processFail(response) 
                    || 'Ошибка обновления данных';
                this.setMessage(message, true);
            },this))

            .always($.proxy(function(){
               this.hideSpinner();
               this.showMessage();
//                    this.enableInputs();
            },this));
        },
        
        confirmCommand: function(command, postTypes){
            var operationInProgress = this.isOperationInProgress();
            
            if(operationInProgress){
                return;
            }

            var commandTitle = '';
            var typeTitles = [];
            switch (command){
                case 'create-index':
                    commandTitle = this.getLinkCreateIndex('selected').text();
                    break;
                case 'update-index':
                    commandTitle = this.getLinkUpdateIndex('selected').text();
                    break;
                case 'delete-index':
                    commandTitle = this.getLinkDeleteIndex('selected').text();
                    break;
            }
            var info = this.get('postTypeInfo');
            for(var i in postTypes){
                var postType = postTypes[i];
                typeTitles.push(info[postType].label); 
            }
            var text = 'Выполнить операцию<br/>&quot;'+commandTitle+'&quot;<br/>для следующих типов записей?<br/><ul><li>'+typeTitles.join('</li><li>')+'</li></ul>';
            if('delete-index' == command){
                window.modalConfirm(text, $.proxy(function(){
                    this.hideProgressBox();
                    this.deleteIndex(postTypes);
                }, this), 'title');
                
            }else{
                window.modalConfirm(text, $.proxy(function(){
                    this.get('progressSpinner').show('Выполнение операции...');
                    this.showProgressBox(true);

                    this.processCommand(command, postTypes);
                }, this), 'title');
            }
        },
        
        processCommand: function(command, postTypes, number){
            number = number || this.get('itemsPerIteration');
            this.set('operation.number', number); 
            if(command && postTypes){
                this.set('operation', {
                    'postTypes': postTypes,
                    'command': command
                });
                this.render();
//                this.set('operation.postTypes', postTypes); 
//                this.set('operation.command', command); 
            }else{
                postTypes = this.get('operation.postTypes'); 
                command = this.get('operation.command'); 
                
            }
            console.log('processing "'+command+'" for '+postTypes.join(', '));
            var url = '';
            var data = {postType: postTypes.join(',')};
            switch (command){
                case 'create-index':
                    url='/api/indexer/index-posts/';
                    data.number = number;
                    break;
                case 'update-index':
                    url='/api/indexer/index-posts/';
                    data.number = number;
                    data.update = 1;
                    break;
            }
            $.ajax(url, {
                data:data,
                dataType: 'json',
                type: 'post'
            })

            .done($.proxy(function(data){
                console.dir({'data': data});
                if(0 == data.code){
                    if(data.payload.start){
                        this.set('operation.start', data.payload.start);
                        this.set('operation.total', data.payload.posts_found)
                        this.addLogMessage('Operation started: '+data.payload.start);
                    }
                    if(!this.get('operation.total')){
                        this.set('operation.total', data.payload.posts_found);
                    }
                    for(var i in this.get('operation.postTypes')){
                        var postType = this.get('operation').postTypes[i];
                        this.options.postTypeInfo[postType].indexed 
                            = data.payload.posts_indexed[postType];
                    }
                    for( var i in data.payload.log){
                        var message=data.payload.log[i];
                        this.addLogMessage(message);
                    }
                    var total = this.get('operation.total');
                    this.setProgress(total - data.payload.posts_left, total);
                    if(data.payload.stop){
                        this.set('operation.stop', data.payload.stop);
                        this.addLogMessage('Operation finished: '+data.payload.stop);
                        this.get('progressSpinner').hide();
                    }else{
                        this.processCommand();
                    }
                    this.render();
                    this.clearMessage();
                }else{
                      this.handleAjaxErrors(data);
                }
            },this))

            .fail($.proxy(function(response){
                var number = this.get('operation.number');
                number = Math.floor(number * 0.6);
                if(number){
                    this.processCommand(null, null, number);
                }else{
                    this.get('progressSpinner').hide();
                    var message = $.brx.utils.processFail(response) 
                        || 'Ошибка выполнения операции';
                    this.setMessage(message, true);
                    this.showMessage();
                    
                }
                console.error(response);
            },this))

            .always($.proxy(function(){
//               this.hideSpinner();
//               this.showMessage();
//                    this.enableInputs();
            },this));
        },
        
        deleteIndex: function(postTypes){
            console.log('deleting index for '+postTypes.join(', '));
                        this.clearMessage();
            this.showSpinner('Очистка индекса...');
            $.ajax('/api/indexer/delete-posts', {
                data:{
                    postType: postTypes?postTypes.join(','):''
                },
                dataType: 'json',
                type: 'post'
            })

            .done($.proxy(function(data){
                console.dir({'data': data});
                if(0 == data.code){
                    for(var postType in data.payload){
                        this.options.postTypeInfo[postType] = 
                            data.payload[postType];
                    }
                    this.render();
                    this.clearMessage();
                }else{
//                        this.processErrors(data.message);
                      this.handleAjaxErrors(data);
                }
            },this))

            .fail($.proxy(function(response){
                var message = $.brx.utils.processFail(response) 
                    || 'Ошибка очистки индекса';
                this.setMessage(message, true);
            },this))

            .always($.proxy(function(){
               this.hideSpinner();
               this.showMessage();
//                    this.enableInputs();
            },this));
        }
        
    });
}(jQuery))


