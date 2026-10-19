' EMO Tracker's launcher. The desktop icon runs it, and so does Windows when
' it starts (with "/startup"). It makes sure the database (Laragon's MySQL)
' and EMO Tracker's server are running, then opens EMO Tracker in the browser.
' At startup it only starts them, without opening the browser.
Option Explicit

' 127.0.0.1 rather than "localhost", which Windows may first try as IPv6 (::1),
' where the server isn't listening.
Const APP_ADDRESS = "http://127.0.0.1:8000/"
Const CHECK_ADDRESS = "http://127.0.0.1:8000/up"
Const LARAGON = "C:\laragon\laragon.exe"

Dim shell, fso, here, atStartup, i
Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
here = fso.GetParentFolderName(WScript.ScriptFullName)
atStartup = False
If WScript.Arguments.Count > 0 Then atStartup = (LCase(WScript.Arguments(0)) = "/startup")

' 1. The database. Laragon starts MySQL ("Start All automatically" is on).
If Not IsRunning("mysqld.exe") Then
  If Not IsRunning("laragon.exe") And fso.FileExists(LARAGON) Then
    shell.Run """" & LARAGON & """", 7, False
  End If
  For i = 1 To 90
    If IsRunning("mysqld.exe") Then Exit For
    WScript.Sleep 500
  Next
End If

' 2. EMO Tracker's server, in a hidden window.
If Not IsUp() Then
  shell.Run """" & here & "\run-server.bat""", 0, False
  For i = 1 To 40
    WScript.Sleep 500
    If IsUp() Then Exit For
  Next
End If

If Not IsUp() Then
  If Not atStartup Then
    MsgBox "EMO Tracker could not start." & vbCrLf & vbCrLf & _
      "Restart the laptop and try again. If it still doesn't open, " & _
      "ask the EMO Tracker team for help.", vbExclamation, "EMO Tracker"
  End If
  WScript.Quit 1
End If

' 3. Open EMO Tracker in the browser.
If Not atStartup Then shell.Run APP_ADDRESS

' Whether a program is running, by its file name.
Function IsRunning(name)
  Dim found
  Set found = GetObject("winmgmts:\\.\root\cimv2").ExecQuery( _
    "SELECT ProcessId FROM Win32_Process WHERE Name = '" & name & "'")
  IsRunning = (found.Count > 0)
End Function

' Whether EMO Tracker's server answers.
Function IsUp()
  Dim http
  IsUp = False
  On Error Resume Next
  Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
  http.setTimeouts 2000, 2000, 3000, 3000
  http.Open "GET", CHECK_ADDRESS, False
  http.Send
  If Err.Number = 0 Then
    If http.Status = 200 Then IsUp = True
  End If
  Err.Clear
  On Error GoTo 0
End Function
