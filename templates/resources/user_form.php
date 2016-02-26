{% block fields %}
	
	{% include 'form_elements/input_formgroup.php' with {
		field:'main_ctrl.resource.data.attributes.first_name',
		label:'First Name',
		required: true,
	} %}

	{% include 'form_elements/input_formgroup.php' with {
		field:'main_ctrl.resource.data.attributes.last_name',
		label:'Last Name',
		required: true,
	} %}

	{% include 'form_elements/input_formgroup.php' with {
		field:'main_ctrl.resource.data.attributes.onyen',
		label:'Onyen',
	} %}

	{% include 'form_elements/input_formgroup.php' with {
		field:'main_ctrl.resource.data.attributes.unc_pid',
		label:'PID',
	} %}

	{% include 'form_elements/input_formgroup.php' with {
		field:'main_ctrl.resource.data.attributes.email',
		label:'Email',
	} %}

	{% include 'form_elements/relation.php' with {
		label:'Roles',
		element: 'checkbox',
		relationship: 'roles',
	} %}	

{% endblock %}