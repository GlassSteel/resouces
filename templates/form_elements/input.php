<input
	type="text"
	class="form-control"
	id="{{ field }}"
	{% if getterSetter is defined and getterSetter %}
		ng-model-options="{getterSetter: true}"
		ng-model="main_ctrl.{{getterSetter}}"
	{% else %}
		ng-model="{{ ngModel | default(field) }}"
	{% endif %}
>