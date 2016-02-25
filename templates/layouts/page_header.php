{% set tag = tag|default('h3') %}
{% set _right = block('right') %}

<div class="page-header clearfix">
	<{{tag}} class="pull-left">
		{% block left %}
		{% endblock %}
	</{{tag}}>
	{% if _right is not empty %}
	<div class="pull-right">
		{{ _right }}
	</div>
	{% endif %}
</div>