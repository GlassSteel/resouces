<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{% if title is defined %}{{ title }} | {% endif %}{{ constant('SITE_NAME') }}</title>
    
    <link href="/assets/css/lib.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ path_for('home') }}">
                    <span class="fa fa-home"></span>&nbsp;
                    <span class="hidden-xs">
                        {{ constant('SITE_NAME') }}
                    </span>
                    <span class="visible-xs-inline" style="font-size:14px;">
                        {{ constant('SITE_NAME') }}
                    </span>
                </a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <div class="navbar-form navbar-right">
                    <a href="{{ path_for('dashboard') }}">
                        <button type="button" class="btn btn-md btn-info">
                            {% if( is_auth ) %}
                                <span class="fa fa-cog"></span>&nbsp;
                                Dashboard
                            {% else %}
                                <span class="fa fa-sign-in"></span>&nbsp;
                                Sign In
                            {% endif %}
                        </button>
                    </a>
                    {% if( is_auth ) %}
                    <a href="TODO">
                        <button type="button" class="btn btn-md btn-info">
                            <span class="fa fa-graduation-cap"></span>
                                Awards Info
                        </button>
                    </a>
                    {% endif %}
                    <a href="TODO">
                        <button type="button" class="btn btn-md btn-info">
                            <span class="fa fa-question-circle"></span>
                                Contact
                        </button>
                    </a>
                </div><!-- .navbar-form.navbar-right -->
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </nav>

    <!-- Page Content -->
    <div class="container clearfix">
        {% block content %}{% endblock %}
    </div>
    <!-- /.container -->
    
    <!-- Footer -->
    <footer class="clearfix">
        <div class="container">
            <div class="row ruled_footer">
                <div class="col-xs-6 col-sm-6 col-md-4">
                    <img class="img-responsive" src="/assets/images/logo_750w.png"/>
                </div>
            </div><!-- .row -->
            <div class="row">
                <div class="col-xs-12 text-center">
                    <p>
                        <!-- TODO dynamic date -->
                        <small>&copy; {{ '2015' }} UNC Gillings School of Global Public Health</small>
                    </p>
                </div>
            </div>
      </div><!-- .container -->
    </footer>
    <!-- ./ Footer -->

    <!-- Scripts -->
    
    <script type="text/javascript" src="/assets/js/lib.min.js"></script>

    <script>
        /**
         * Lets you use underscore as a service from a controller.
         * Got the idea from: http://stackoverflow.com/questions/14968297/use-underscore-inside-controllers
         * @author: Andres Aguilar https://github.com/andresesfm
         */
        angular.module('underscore', []).factory('_', function() {
            return window._; // assumes underscore has already been loaded on the page
        });
    </script>
    
    {% block script %}{% endblock %}
</body>
</html>