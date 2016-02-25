{% if label is defined and label %}
  {% set words = label | split(' ') %}
  <label for="{{ field | default('') }}" class="col-sm-3 control-label">
    {% for word in words %}
      
      {% if loop.last %}
        <span class="last_word">
      {% endif %}
      
      {{ word }}
      
      {% if loop.last %}
        {% if required is defined and required %}<sup>*</sup>{% endif %}
        </span>
      {% endif %}
    
    {% endfor %}
  </label>
{% else %}
  <div class="col-sm-3"></div>
{% endif %}
