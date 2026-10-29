"use strict";
/** global: com_jgive */

window.com_jgive.UI.Organizations = {
	redirectForMail : function(task)
	{
		if(document.adminForm.boxchecked.value == 0)
		{
			alert(Joomla.Text._("TTOOLBAR_NO_SELECT_MSG"));
			return false;
		}

		return Joomla.submitform(task);
	}
}
