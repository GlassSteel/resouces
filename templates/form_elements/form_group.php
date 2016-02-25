<div class="form-group" {% if field is defined %}ng-class="main_ctrl.hasError('{{ field }}') ? 'has-error' : ''"{% endif %}>
    {{ include('form_elements/label.php') }}
    <div class="col-sm-9">
      	{% block element %}{% endblock %}
        {{ include('form_elements/help_block.php') }}
    </div>
</div><!-- .form-group -->