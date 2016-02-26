{% extends 'form_elements/form_group.php' %}

{% block element %}
	<div
    ng-controller="RelationOptions_Controller as options_ctrl"
    ng-init="options_ctrl.setOptions(main_ctrl.resource.meta.related_collections.{{ relationship }}.url)"
  >
    <div ng-repeat="option in options_ctrl.options">
  		{% if element == 'checkbox' %}
  			<div class="checkbox">
  			  <label>
  			    <input
  			    	type="checkbox"
              ng-model-options="{getterSetter: true}"
              ng-model="main_ctrl.relationGS(
                option.id,
                '{{ relationship }}',
                main_ctrl.resource.meta.related_collections.{{ relationship }}.type
              )"
  			    />
  			    {{'{{'}} option.meta.instance_name {{'}}'}}
  			  </label>
  			</div>
  		{% endif %}
  	</div>
  </div>
{% endblock %}