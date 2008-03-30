/*
 * This file is part of Appcelerator.
 *
 * Copyright (C) 2006-2008 by Appcelerator, Inc. All Rights Reserved.
 * For more information, please visit http://www.appcelerator.org
 *
 * Appcelerator is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


Appcelerator.Widget.Message =
{
	getName: function()
	{
		return 'appcelerator message';
	},
	getDescription: function()
	{
		return 'message widget for generating messages (either remote or local)';
	},
	getVersion: function()
	{
		return '1.0.2';
	},
	getSpecVersion: function()
	{
		return 1.0;
	},
	getAuthor: function()
	{
		return 'Jeff Haynie';
	},
	getModuleURL: function ()
	{
		return 'http://www.appcelerator.org';
	},
	isWidget: function ()
	{
		return true;
	},
	getWidgetName: function()
	{
		return 'app:message';
	},
	getActions: function()
	{
		return ['execute','stop'];
	},	
	getAttributes: function()
	{
        var T = Appcelerator.Types;
        return [{
            name: 'on',
            optional: true,
            type: T.onExpr,
            description: "May be used to express when the message should be fired (executed)."
        }, {
            name: 'name',
            optional: false,
            type: T.messageSend,
            description: "The name of the message to be fired."
        }, {
            name: 'args',
            optional: true,
            type: T.json,
            description: "The argument payload of the message."
        }, {
            name: 'version',
            optional: true,
            description: "The version attached to the message."
        }, {
            name: 'interval',
            optional: true,
            type: T.time,
            description: "Indicates that an time interval that the message will continously be fired."
        }]
	},
	
	execute: function(id,parameterMap,data,scope,version)
    {
        Appcelerator.Widget.Message.sendMessage(parameterMap);
    },
    stop: function(id,parameterMap,data,scope,version)
    {
        var timer = parameterMap['timer'];
        if(timer)
        {
            clearInterval(timer);
            parameterMap['timer'] = null;
        }
        else
        {
            $D('Message '+parameterMap['name']+' is not currently sending, cannot stop');
        }
    },
	compileWidget: function(parameters)
	{
		Appcelerator.Widget.Message.sendMessage(parameters);
	},
	buildWidget: function(element, attributes)
	{
		var name = attributes['name'];
		var args = attributes['args'];
		var version = attributes['version'];
		var on = attributes['on'];
		
		if (args)
		{
			args = String.unescapeXML(args).replace(/\n/g,'').replace(/\t/g,'');
		}
		
		var interval = attributes['interval'];
		
		var parameters = {args:args, name:name, scope:element.scope, interval:interval,version:version};
		
		if (on)
		{
			return {
				'position' : Appcelerator.Compiler.POSITION_REMOVE,
				'parameters': parameters
			};
		}
		else
		{
			return {
				'position' : Appcelerator.Compiler.POSITION_REMOVE,
				'compile': true,
				'parameters': parameters
			};
		}
	},
	/*
	 * If the widget has an interval set, begin sending polling messages,
	 * otherwise send a one-shot message.
	 */
	sendMessage: function(params)
	{
		var name = params.name;
		var args = params.args;
		var version = params.version;
		var scope = params.scope;
		var interval = params.interval;
		var data = null;
		
		if (args && args != 'null')
        {
            data = Object.evalWithinScope(args, window);
        }

        if (interval == null || !params['timer']) $MQ(name, data, scope, version);      
        if (interval != null)
        {
            var time = Appcelerator.Util.DateTime.timeFormat(interval);

            if (time > 0 && !params['timer'])
            {
                params['timer'] = setInterval(function()
                {
                    if (args && args != 'null')
                    {
                    	// re-evaluate each time so you can dynamically change data each interval
                    	data = Object.evalWithinScope(args, window);
                    }
                    $MQ(name, data, scope, version);
                }, time);
            }
        }
	}
};

Appcelerator.Widget.register('app:message',Appcelerator.Widget.Message);
