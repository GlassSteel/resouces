<input
	type="text"
	class="form-control"
	id="{{ field }}"
	{% if getterSetter is defined and getterSetter %}
		ng-model-options="{
			getterSetter: true,
			updateOn: 'default blur',
			debounce: { 'default': 500, 'blur': 0 }
		}"
		ng-model="{{getterSetter}}"
	{% else %}
		ng-model="{{ ngModel | default(field) }}"
	{% endif %}
>