{% extends 'layouts/main.php' %}

{% block content %}

	<form
		class="form-horizontal"
		ng-controller="Form_Controller as main_ctrl"
		id="ThisForm"
	>
		<div class="row">
			<div class="col-xs-12">
		        {% embed 'layouts/page_header.php' with {'tag' : 'h3'} %}
		        	{% block left %}
		        		<span ng-if="main_ctrl.form_method != main_ctrl.default_form_method">
		        			<small>Edit {{ resource.data.meta.resource_nicename }}</small><br />
		        			{{'{{'}} main_ctrl.resource.data.meta.instance_name {{'}}'}}
		        		</span>
		        		<span ng-if="main_ctrl.form_method == main_ctrl.default_form_method">
		        			Create New {{ resource.data.meta.resource_nicename }}
		        		</span>
		        	
		        	{% endblock %}
		        {% endembed %}
	        </div>
	    </div>

	    {% if true %}
	    <pre>{{'{{'}}main_ctrl.resource | json {{'}}'}}</pre>
	    {% endif %}
	    
		{% include 'resources/' ~ resource.data.type ~ '_form.php' %}

		{% embed 'form_elements/form_group.php' with {} %}
			{% block element %}
				<p ng-if="main_ctrl.hasFlash()">
					<uib-alert
						class="alert"
						type="{{'{{'}}main_ctrl.flash_type{{'}}'}}" 
						close="main_ctrl.clearFlash()"
					>
						{{'{{'}} main_ctrl.flash {{'}}'}}
					</uib-alert>
				</p>

				<div
					class="btn btn-md btn-primary"
					ng-click="main_ctrl.submitForm()"
					ng-disabled="main_ctrl.submitIsDisabled()"
				>
					{{'{{'}} main_ctrl.getBtnTxt() {{'}}'}}
					<span ng-show="main_ctrl.submitIsDisabled()" class="fa fa-spinner fa-spin"></span>
				</div>
			{% endblock %}
		{% endembed %}

	</form>
{% endblock %}

{% block script %}
	<script>
	
	angular
	    .module('ThisForm',['ui.bootstrap','ngFileUpload','underscore'])
	    .controller('Form_Controller',Form_Controller)
	    .controller('RelationOptions_Controller',RelationOptions_Controller)
	;
	
	angular.element(document).ready(function() {
	    var el = document.getElementById('ThisForm');
	    angular.bootstrap(el, ['ThisForm']);
	});

	function RelationOptions_Controller($http){
		var vm = this;
		vm.options = {};

		vm.setOptions = function(collection_url){
			$http({
	    		method: 'get',
	    		url: collection_url,
	    		headers: {
	    			'Accept' : '{{ constant('JSONAPI_MEDIA_TYPE') }}',
	    			'Content-Type' : '{{ constant('JSONAPI_MEDIA_TYPE') }}'
	    		}
	    	}).then(function successCallback(response) {
	    		vm.options = response.data.data;
	    	}, function errorCallback(response) {
    			console.log(response);
    		});
		}
	}

	function Form_Controller($http,$timeout){
	    var vm = this;
	    vm.default_form_method = 'post';
	    vm.edit_form_method = 'patch';
		vm.resource = {{ resource|json_encode()|raw }};
	    vm.form_method = {% if resource.data.id %}{{ 'vm.edit_form_method' }}{% else %}{{ 'vm.default_form_method' }}{% endif %};
	    vm.form_action = vm.resource.links.self;
	    vm.waiting = false;
	    vm.flash = false;
	    vm.flash_type = 'success';
	    vm.errors = false;
	    vm.old_resource = angular.copy(vm.resource);

	    vm.relationGS = function(option_id,relationship,type){
	    	return function(val){
	    		var relateds = vm.resource.data.relationships[relationship];
	    		if ( angular.isDefined(val) ){
	    			if ( val ){
	    				if ( !_.find(relateds, function(el, index, list){
	    					return ( angular.isDefined(el) && el.id == option_id );
	    				}) ){
	    					relateds.push({
	    						'type' : type,
	    						'id' : option_id,
	    					});
	    				}
	    			}else{
	    				_.each(relateds, function(ele, index, list){
	    					if ( angular.isDefined(ele) && ele.id == option_id ){
	    						relateds.splice(index,1);
	    					}
	    				});
	    			}
	    		}
	    		var has = _.find(relateds, function(element, index, list){
	    			return ( angular.isDefined(element) && element.id == option_id );
	    		});
	    		return angular.isDefined(has);
	    	};
	    }

	    vm.clearFlash = function(){
	    	vm.flash = false;
	    }

	    vm.hasFlash = function(){
	    	return (vm.flash && !vm.waiting);
		}

		vm.hasError = function(field){
			field = field.split('.').pop();
			if ( angular.isDefined( vm.errors[field]) ){
				return true;
			}
			return false;
		}//hasError()

		vm.getError = function(field){
			field = field.split('.').pop();
			var err = vm.errors[field];
			var msg = '';
			switch( err ){
				case 'not_unique':
					msg += angular.copy('"' + vm.old_resource.data.attributes[field] + '" is already in use. Please enter a different value');
				break;
				case 'missing_required':
					msg += 'This field is required.'
				break;
					msg += 'Please enter a valid value.';
				default:

			}
			return [msg];
		}//getError()

	    vm.submitIsDisabled = function(){
	    	if ( vm.waiting ){
	    		return true;
	    	}
	    	return false;
	    }//submitIsDisabled()

	    vm.submitForm = function(){
	    	if ( vm.waiting === true ){
		    	return;
		    }
		    vm.clearFlash();
		    vm.waiting = true;
		    vm.errors = false;
	    	var data = angular.copy(vm.resource);

	    	//TODO replace these with loop on properties
	    	delete data.meta;
	    	delete data.links;

			$http({
	    		method: vm.form_method,
	    		url: vm.form_action,
	    		data: JSON.stringify(data),
	    		headers: {
	    			'Accept' : '{{ constant('JSONAPI_MEDIA_TYPE') }}',
	    			'Content-Type' : '{{ constant('JSONAPI_MEDIA_TYPE') }}'
	    		}
	    	}).then(function successCallback(response) {
	    		vm.delayButtonRestore(response,true);
	    	}, function errorCallback(response) {
    			vm.delayButtonRestore(response,false);
    		});
		}//submitForm()

		vm.delayButtonRestore = function(response,success){
			if ( vm.waiting === false ){
		    	return;
		    }
			var timer = $timeout(
				function(){
					vm.waiting = false;
					var was_new = false;
					$timeout.cancel( timer );

					vm.old_resource = angular.copy(vm.resource);

					if (success) {
						if ( !vm.resource.data.id ){
							was_new = true;
						}
						vm.resource = angular.copy(response.data);
						if ( was_new ){
							vm.form_method = vm.edit_form_method;
							vm.form_action = vm.resource.links.self;
							vm.flash = 'Your new ' + vm.resource.data.meta.resource_nicename + ' "' + vm.resource.data.meta.instance_name + '" has been created.';
						}else{
							vm.flash = 'Your edits to "' + vm.resource.data.meta.instance_name + '" have been saved.';
						}
						vm.flash_type = 'success';
						vm.flash += ' Please make any additional edits as desired.';
					}else{
						//TODO handle {"errors":{"code":"failed_save","title":"Your submission could not be saved due to an internal error"}}
						vm.flash_type = 'danger';
  						vm.flash = response.data.errors.title;
  						vm.errors = response.data.errors.meta.fields;
					}
				},
				500
			);
		}//delayButtonRestore()

		vm.getBtnTxt = function(){
			if (vm.form_method == vm.default_form_method){
				return 'Submit New {{ resource.data.meta.resource_nicename }}';
			}
			return 'Submit Edits';
		}//getBtnTxt

		{% include 'resources/' ~ resource.data.type ~ '_formcontroller.js' ignore missing %}

	}//Form_Controller()

	</script>
{% endblock %}