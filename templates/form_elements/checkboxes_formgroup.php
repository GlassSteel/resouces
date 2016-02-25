{% extends 'form_elements/form_group.php' %}

{% block element %}
	{% for cb in checkboxes %}
	  {% include 'form_elements/checkbox.php' with {
	  	cb : cb,
	  } %}
	{% endfor %}
{% endblock %}