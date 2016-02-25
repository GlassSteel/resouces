{% if note is defined and note %}
<p class="help-block">
  {{ note }}
</p>
{% endif %}
{% if field is defined %}
<p
  ng-if="main_ctrl.hasError('{{ field }}')"
  ng-repeat="error in main_ctrl.getError('{{ field }}')"
  class="help-block text-danger"
>
  {{'{{'}} error {{'}}'}}
</p>
{% endif %}