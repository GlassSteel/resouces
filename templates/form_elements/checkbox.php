<div class="checkbox">
  <label>
    <input
    	type="checkbox"
    	{% if getterSetter is defined and getterSetter %}
    		ng-model-options="{getterSetter: true}"
    		ng-model="{{getterSetter}}({{ cb.value }})"
    	{% else %}
    		ng-model="{{ cb.ngModel | default(field ~ '.' ~ cb.value ) }}"
    	{% endif %}
    />
    {{ cb.label }}
  </label>
</div>