{% block fields %}
	
	{% include 'form_elements/input_formgroup.php' with {
		field:'main_ctrl.resource.data.attributes.name',
		label:'Name',
		required: true,
	} %}

	{% include 'form_elements/textarea_formgroup.php' with {
		field:'main_ctrl.resource.data.attributes.description',
		label:'Description',
	} %}

	{% include 'form_elements/input_formgroup.php' with {
		field:'main_ctrl.resource.data.attributes.slug',
		label:'Slug',
		required: true,
	} %}

	{% include 'form_elements/relation.php' with {
		label:'Capabilities',
		element: 'checkbox',
		relationship: 'capabilities',
	} %}	

{% endblock %}