' Makes the "EMO Tracker" icon on the desktop, and a shortcut in the Startup
' folder so EMO Tracker starts with Windows. "Setup EMO Tracker.bat" runs this.
Option Explicit

Dim shell, fso, here
Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
here = fso.GetParentFolderName(WScript.ScriptFullName)

MakeShortcut shell.SpecialFolders("Desktop") & "\EMO Tracker.lnk", ""
MakeShortcut shell.SpecialFolders("Startup") & "\EMO Tracker.lnk", " /startup"

Sub MakeShortcut(path, extra)
  Dim link
  Set link = shell.CreateShortcut(path)
  link.TargetPath = shell.ExpandEnvironmentStrings("%WINDIR%\System32\wscript.exe")
  link.Arguments = """" & here & "\EMO Tracker.vbs""" & extra
  link.WorkingDirectory = here
  link.IconLocation = here & "\emo-tracker.ico,0"
  link.Description = "Open EMO Tracker"
  link.Save
  WScript.Echo "  Made: " & path
End Sub
