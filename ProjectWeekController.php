<?php

namespace App\Http\Controllers;

use App\Models\ProjectWeekPupil;
use App\Models\ProjectWeek;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use LdapRecord\Models\ActiveDirectory\Group;
use phpDocumentor\Reflection\Types\Integer;

class ProjectWeekController extends Controller
{
    public function access()
    {
        $entered = false;
        $checked = '';
        $projectData = [];
        $anyUncheckedPupils = false;
        $pupilNumber = 0;
        $projectNumber = 0;
        $uncheckedPupils = 0;
        $teacherData = [];
        if (Gate::allows("view-classes-projectweek")) {
            $name = Auth::user()->name;                                                                 /* get Benutzername */
            $pupil = ProjectWeekPupil::where('name', $name)->get();                                     /* get ProjectwochenTeilnehmer */
            if (count($pupil) >= 1) {
                $entered = true;                                                                        /* eingetragen */
                if ($pupil[0]->checked == 1) {                                                          /* wenn bestätigt */
                    $checked = 'bestätigt';                                                                 /* "bestätigt" schrieben */
                } elseif ($pupil[0]->checked == 0) {                                                    /* sonst */
                    $checked = 'nicht bestätigt';                                                           /* "nicht betätigt" schreiben */
                }
                $project = ProjectWeek::where('id', $pupil[0]->projectID)->first();                     /* get ProjectID */
                $teacher = User::where('username', $project->teacher)->get();                           /* get Lehrername */
                if (count($teacher) >= 1) {                                                             /* wenn name lang ist */
                    $teachername = substr($teacher[0]->firstname, 0, 1) . '. ' . $teacher[0]->lastname;      /* Ja */     /* Lehrernamen kürzen */ /* Lehrename eintragen */
                } else {
                    $teachername = $project->teacher;                                                        /* Nein */   /* nicht kürzen */ /* Lehrername eintragen */
                }
                $projectData = [                                                                        /* Projectdata eintragen */
                    'title' => $project->title,                                                             /* Pojecttitel eintragen */
                    'subject' => $project->subject,                                                         /* Projectbeschreibung eintragen */
                    'teacher' => $teachername,                                                              /* Lehrername eintragen */
                    'checked' => $checked                                                                   /* Bestätigungsstatus eintragen */
                ];
            }
        }
        if (Gate::allows("view-teacher-projectweek")) {
            $username = Auth::user()->username;
            $projects = ProjectWeek::where('teacher', $username)->get();
            $projectNumber = 0;
            $pupilNumber = 0;
            $uncheckedPupils = 0;
            if (count($projects) >= 1) {
                $projectNumber = count($projects);
                foreach ($projects as $project) {
                    $pupils = ProjectWeekPupil::where('projectID', $project->id)->get();
                    $pupilNumber = $pupilNumber + count($pupils);
                    foreach ($pupils as $pupil) {
                        if ($pupil->checked == 0) {
                            $anyUncheckedPupils = true;
                            $uncheckedPupils = $uncheckedPupils + 1;
                        }
                    }
                }
            }
            $teacherData = [
                'projectNumber' => $projectNumber,
                'pupilNumber' => $pupilNumber,
                'uncheckedPupils' => $uncheckedPupils
            ];
        }

        $search = false;
        if (Auth::user()->username == "schollmann" || Auth::user()->isSupervisor()) {
            $search = true;
        }

        $data = [
            'viewClasses' => Gate::allows("view-classes-projectweek"),
            'viewSupervisor' => Auth::user()->isSupervisor(),
            'viewClassesEntered' => $entered,
            'projectData' => $projectData,
            'viewTeacher' => Gate::allows("view-teacher-projectweek"),
            'viewAdmin' => $search,
            'viewTeacherUncheckedPupils' => $anyUncheckedPupils,
            'teacherProjects' => $teacherData
        ];
        return $data;
    }

    public function projectList()
    {
        $projects = ProjectWeek::orderBy('title')->get();
        return $projects;
    }

    public function teacherProjects()
    {
        $username = Auth::user()->username;
        $projects = ProjectWeek::where('teacher', $username)->get();

        $returning = [];
        foreach ($projects as $project) {
            $pupils = ProjectWeekPupil::where('projectID', $project->id)->get();
            foreach ($pupils as $pupil) {
                $returning[] = [
                    'id' => $pupil->id,
                    'name' => $pupil->name,
                    'class' => $pupil->class,
                    'email' => $pupil->email,
                    'homework' => $pupil->homework,
                    'checked' => $pupil->checked,
                    'projectTitle' => $project->title,
                    'projectMembers' => $project->members
                ];
            }
        }

        return $returning;
    }

    public function teacherList()
    {
        $group = Group::find('cn=lehrer,cn=Users,dc=sgym,dc=intern');
        $teachers = $group->members()->get();
        $list = [];

        foreach ($teachers as $teacher) {
            if ($teacher->sn[0] != "Test" && $teacher->sn[0] != "Bock" && $teacher->sn[0] != "Dux" && $teacher->sn[0] != "Ewald" && $teacher->sn[0] != "Theile" && $teacher->sn[0] != "Schmidt" && $teacher->sn[0] != "Thiele" && $teacher->sn[0] != "Boye" && $teacher->sn[0] != "Kannengießer" && $teacher->sn[0] != "Hünecke" && $teacher->sn[0] != "Wertan" && $teacher->sn[0] != "Fessel") {
                $list[] = [
                    'name' => $teacher->sn[0],
                    'firstname' => $teacher->givenName[0],
                    'id' => $teacher->uid[0]
                ];
            }
        }
        sort($list);
        return $list;
    }

    public function projectData()
    {
        $name = Auth::user()->name;
        $homework = false;
        $pupil = ProjectWeekPupil::where('name', $name)->first();
        if ($pupil->homework == 1) {
            $homework = true;
        }
        $project = ProjectWeek::where('id', $pupil->projectID)->first();
        $teacher = User::where('username', $project->teacher)->get();
        if (count($teacher) >= 1) {
            $teachername = $teacher[0]->lastname;
        } else {
            $teachername = $project->teacher;
        }
        $projectData = [
            'name' => $pupil->name,
            'grade' => $pupil->class,
            'email' => $pupil->email,
            'homework' => $homework,
            'title' => $project->title,
            'subject' => $project->subject,
            'teacher' => $teachername
        ];
        return $projectData;
    }

    public function classData(string $grade)
    {
        $students = [];
        if ($grade != 'allclasses'){
            if (strlen($grade) == 3) {
                $class = $grade[0] . $grade[2];
            } else if (strlen($grade) == 4) {
                $class = $grade[0] . $grade[1] . $grade[3];
            }

            $group = Group::find('cn=' . $class . ',cn=Users,dc=sgym,dc=intern');
            $students = $group->members()->get();
        }
        else{
            $classes = array("51", "52", "53", "61", "62", "63", "71", "72", "73", "81", "82", "83", "91", "92", "93", "101", "102", "103", "111", "112", "113", "121", "122", "123");
            foreach($classes as $class){
                $group = Group::find('cn='.$class.',cn=Users,dc=sgym,dc=intern');
                $st = $group->members()->get();
                $stest = [];
                foreach($st as $s){
                    $stest[] = [
                        'Nachname' => $s->sn[0],
                        'Vorname' => $s->givenname[0],
                        'Username' => $s->cn[0],
                        'Klasse' => $class
                    ];
                }
                sort($stest);
                foreach($stest as $s){
                    $students[] = [
                        'Nachname' => $s['Nachname'],
                        'Vorname' => $s['Vorname'],
                        'Username' => $s['Username'],
                        'Klasse' => $s['Klasse']
                    ];
                }
            }

        }

        if ($grade!='allclasses')  $enteredStudents = ProjectWeekPupil::where('class', $grade)->get();
        else $enteredStudents = ProjectWeekPupil::all();
        $studentsData = [];

        foreach ($students as $student) {
                $registered = "nein";
                $checked = "nein";
                $projectTitle = "";
                $projectTeacher = "";

                if ($grade != 'allclasses'){
                    if ($student->sn[0] != "Test") {
                        foreach ($enteredStudents as $enteredStudent) {
                            if ($student->cn[0] == $enteredStudent->name) {
                                $registered = "ja";
                                $project = ProjectWeek::where('id', $enteredStudent->projectID)->first();
                                $projectTitle = $project->title;
                                $projectTeacher = $project->teacher;
                                if ($enteredStudent->checked == 1) {
                                    $checked = "ja";
                                }
                                break;
                            }
                        }


                        $studentsData[] = [
                            'Nachname' => $student->sn[0],
                            'Vorname' => $student->givenname[0],
                            'Klasse' => $grade,
                            'Eintragung' => $registered,
                            'Bestätigung' => $checked,
                            'Projekt' => $projectTitle,
                            'Lehrer' => $projectTeacher
                        ];
                    }
                }
                else{
                        foreach ($enteredStudents as $enteredStudent) {
                            if ($student['Username'] == $enteredStudent->name) {
                                $registered = "ja";

                                $project = ProjectWeek::where('id', $enteredStudent->projectID)->first();
                                $projectTitle = $project->title;
                                $projectTeacher = $project->teacher;
                                if ($enteredStudent->checked == 1) {
                                    $checked = "ja";
                                }
                                break;
                            }
                        }

                        if (strlen($student['Klasse']) == 2) {
                             $c = $student['Klasse'][0] .".".$student['Klasse'][1];
                        } else if (strlen($student['Klasse']) == 3) {
                             $c = $student['Klasse'][0] . $student['Klasse'][1] ."." .$student['Klasse'][2];
                        }
                        if ($student['Nachname'] != "Test") {
                            $studentsData[] = [
                                'Nachname' => $student['Nachname'],
                                'Vorname' => $student['Vorname'],
                                'Klasse' => $c,
                                'Eintragung' => $registered,
                                'Bestätigung' => $checked,
                                'Projekt' => $projectTitle,
                                'Lehrer' => $projectTeacher
                            ];
                        }

                }

                $teacher = User::where('username', $projectTeacher)->get();
                if (isset($teacher[0])) {
                    $studentsData[count($studentsData) - 1]['Lehrer'] = substr($teacher[0]->firstname, 0, 1) . '. ' . $teacher[0]->lastname;
                }

        }
        if ($grade != 'allclasses') sort($studentsData);
        return $studentsData;
    }

    public function roomData()
    {
        $projects = ProjectWeek::all();
        $data = [];

        foreach ($projects as $project) {
            if ($project->computerDay != "" || $project->computerNumber != 0 || $project->room != "") {
                $days = explode(", ", $project->computerDay);
                foreach ($days as $day) {
                    $data[] = [
                        'computerDay' => $day,
                        'computerNumber' => $project->computerNumber,
                        'room' => $project->room,
                        'title' => $project->title,
                        'teacher' => $project->teacher,
                        'subject' => $project->subject
                    ];
                }
            }
        }
        sort($data);
        $returning = [];
        foreach ($data as $dat) {
            $day = "";
            if ($dat['computerDay'] == "0") {
                $day = "Montag";
            } elseif ($dat['computerDay'] == "1") {
                $day = "Dienstag";
            } elseif ($dat['computerDay'] == "2") {
                $day = "Mittwoch";
            } elseif ($dat['computerDay'] == "3") {
                $day = "Donnerstag";
            } elseif ($dat['computerDay'] == "4") {
                $day = "Freitag";
            } else {
                $day = $dat['computerDay'];
            }
            $returning[] = [
                'computerDay' => $day,
                'computerNumber' => $dat['computerNumber'],
                'room' => $dat['room'],
                'title' => $dat['title'],
                'teacher' => $dat['teacher'],
                'subject' => $dat['subject']
            ];

            $teacher = User::where('username', $dat['teacher'])->get();
            if (isset($teacher[0])) {
                $returning[count($returning) - 1]['teacher'] = substr($teacher[0]->firstname, 0, 1) . '. ' . $teacher[0]->lastname;
            }
        }
        return $returning;
    }

    public function teacherStatistics()
    {
        $group = Group::find('cn=lehrer,cn=Users,dc=sgym,dc=intern');
        $teachers = $group->members()->get();

        $projects = ProjectWeek::all();
        $pupils = ProjectWeekPupil::all();

        $teacherData = [];

        foreach ($teachers as $teacher) {
            if ($teacher->sn[0] != "Test" && $teacher->sn[0] != "Bock" && $teacher->sn[0] != "Dux" && $teacher->sn[0] != "Ewald" && $teacher->sn[0] != "Krieger" &&
                $teacher->sn[0] != "Oberst" && $teacher->sn[0] != "Theile" && $teacher->sn[0] != "Schmidt" && $teacher->sn[0] != "Thiele" && $teacher->sn[0] != "Boye" &&
                $teacher->sn[0] != "Kannengießer" && $teacher->sn[0] != "Hünecke" && $teacher->sn[0] != "Wertan" && $teacher->sn[0] != "Fessel" && $teacher->sn[0] != "Baeumler" &&
                $teacher->sn[0] != "Balandis" && $teacher->sn[0] != "Hase" && $teacher->sn[0] != "Huenecke" && $teacher->sn[0] != "Kroenig" && $teacher->sn[0] != "Peine" &&
                $teacher->sn[0] != "Seibicke" && $teacher->sn[0] != "Thormeyer")
            {
                $projectNumber = 0;
                $uncheckedNumber = 0;
                $checkedNumber = 0;
                foreach ($projects as $project) {
                    if ($teacher->uid[0] == $project->teacher) {
                        $projectNumber = $projectNumber + 1;
                        foreach ($pupils as $pupil) {
                            if ($project->id == $pupil->projectID) {
                                if ($pupil->checked == 1) {
                                    $checkedNumber = $checkedNumber + 1;
                                } else if ($pupil->checked == 0) {
                                    $uncheckedNumber = $uncheckedNumber + 1;
                                }
                            }
                        }
                    }
                }

                $teacherData[] = [
                    'Nachname' => $teacher->sn[0],
                    'Vorname' => $teacher->givenname[0],
                    'Projekte' => $projectNumber,
                    'bestätigteSchüler' => $checkedNumber,
                    'unbestätigteSchüler' => $uncheckedNumber
                ];
            }
        }
        sort($teacherData);
        return $teacherData;
    }

    public function search(string $mode, string $name)
    {
        $returningData = [];
        if ($mode == "Schüler") {
            $student = User::select('name')
                ->where('lastname', 'LIKE', '%' . explode(" ", $name)[count(explode(" ", $name)) - 1] . '%')
                ->where('firstname', 'LIKE', '%' . explode(" ", $name)[0] . '%')
                ->get();
            $pupil = ProjectWeekPupil::where('name', $student[0]['name'])->get();
            if (count($pupil) >= 1) {
                $project = ProjectWeek::where('id', $pupil[0]->projectID)->first();
                $checked = "nicht bestätigt";
                if ($pupil[0]->checked) {
                    $checked = "bestätigt";
                }
                $returningData = [
                    'name' => $pupil[0]->name,
                    'class' => $pupil[0]->class,
                    'project' => $project->title,
                    'subject' => $project->subject,
                    'teacher' => $project->teacher,
                    'checked' => $checked,
                    'entered' => "eingetragen"
                ];
                $teacher = User::where('username', $project->teacher)->get();
                if (isset($teacher[0])) {
                    $returningData['teacher'] = substr($teacher[0]->firstname, 0, 1) . '. ' . $teacher[0]->lastname;
                }
            } else {
                $returningData = [
                    'name' => $name,
                    'entered' => "nicht eingetragen"
                ];
            }
        } elseif ($mode == "Projekt") {
            $project = ProjectWeek::where('id', $name)->get();
            if (count($project) >= 1) {
                $pupils = ProjectWeekPupil::where('projectID', $project[0]->id)->get();
                $pupilData = [];
                foreach ($pupils as $pupil) {
                    $checked = "nicht bestätigt";
                    if ($pupil->checked) {
                        $checked = "bestätigt";
                    }
                    $pupilData[] = [
                        'name' => $pupil->name,
                        'class' => $pupil->class,
                        'checked' => $checked
                    ];
                }
                $members = count($pupils) . "/" . $project[0]->members;
                $returningData = [
                    'title' => $project[0]->title,
                    'subject' => $project[0]->subject,
                    'teacher' => $project[0]->teacher,
                    'members' => $members,
                    'students' => $pupilData
                ];
                $teacher = User::where('username', $project[0]->teacher)->get();
                if (isset($teacher[0])) {
                    $returningData['teacher'] = substr($teacher[0]->firstname, 0, 1) . '. ' . $teacher[0]->lastname;
                }
            }
        } elseif ($mode == "Lehrer") {
            $group = Group::find('cn=lehrer,cn=Users,dc=sgym,dc=intern');
            $teachers = $group->members()->get();
            $teachername = $name;
            foreach ($teachers as $teacher) {
                if ($teacher->uid[0] == $name) {
                    $teachername = $teacher->cn[0];
                }
            }
            $projects = ProjectWeek::where('teacher', $name)->get();
            if (count($projects) >= 1) {
                $projectsData = [];
                foreach ($projects as $project) {
                    $projectsData[] = [
                        'title' => $project->title,
                        'subject' => $project->subject,
                        'members' => $project->members
                    ];
                }
                $returningData = [
                    'name' => $teachername,
                    'projectNumber' => count($projects),
                    'projects' => $projectsData
                ];
            } else {
                $returningData = [
                    'name' => $teachername,
                    'projectNumber' => 0
                ];
            }
        }

        return $returningData;
    }

    public function create(Request $request)
    {
        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'subject' => $request->subject,
            'members' => $request->members,
            'teacher' => $request->teacher,
            'computerDay' => $request->computerDay,
            'computerNumber' => $request->computerNumber,
            'material' => $request->material,
            'room' => $request->room
        ];

        $checkDouble = ProjectWeek::where('title', $request->title)->first();
        if ($checkDouble != null) {
            return response("Das Projekt existiert bereits oder der Projektname ist bereits vergeben", 500)
                ->header("Content-Type", "text/plain");
        }

        $days = '';
        foreach ($data['computerDay'] as $key => $value) {
            if ($value) {
                $days .= $key . ', ';
            }
        }
        if (strlen($days) > 2) {
            $data['computerDay'] = substr($days, 0, -2);
        } else {
            $data['computerDay'] = null;
        }

        ProjectWeek::create($data);

        $project = ProjectWeek::where('title', $request->title)
            ->where('teacher', $request->teacher)
            ->where('members', $request->members)
            ->where('subject', $request->subject)
            ->get();
        $projectID = $project[0]->id;
        $homework = 0;
        if ($request->homework) {
            $homework = 1;
        }

        $data2 = [
            'projectID' => $projectID,
            'name' => Auth::user()->name,
            'class' => Auth::user()->getClass(),
            'email' => $request->email,
            'checked' => 0,
            'homework' => $homework
        ];
        ProjectWeekPupil::create($data2);

        return response("Das Projekt wurde erfolgreich angelegt", 200)
            ->header("Content-Type", "text/plain");
    }

    public function enter(Request $request)
    {
        $homework = 0;
        if ($request->homework) {
            $homework = 1;
        }

        $data = [
            'projectID' => $request->projectID,
            'name' => Auth::user()->name,
            'class' => Auth::user()->getClass(),
            'email' => $request->email,
            'checked' => 0,
            'homework' => $homework
        ];

        $checkDouble = ProjectWeekPupil::where('name', $request->name)
            ->where('class', $request->class)
            ->where('email', $request->email)
            ->first();
        if ($checkDouble != null) {
            return response("Du bist bereits in einem Projekt eintragen", 500)
                ->header("Content-Type", "text/plain");
        }

        $project = ProjectWeek::where('id', $request->projectID)->first();
        $members = ProjectWeekPupil::where('projectID', $request->projectID)->get();

        if ($project['members'] < count($members) + 1) {
            return response("Die Eintragung konnte nicht ausgeführt werden, weil bereits die Obergrenze an teilnehmenden Schülern überschritten wurde.", 500)
                ->header("Content-Type", "text/plain");
        }

        ProjectWeekPupil::create($data);

        return response("Erfolgreich in das Projekt eingetragen", 200)
            ->header("Content-Type", "text/plain");
    }

    public function editChecked(string $id)
    {
        ProjectWeekPupil::where('id', $id)->update(['checked' => 1]);

        return response("Der Schüler wurde erfolgreich bestätigt", 200)
            ->header("Content-Type", "text/plain");
    }

    public function deletePupil(string $id)
    {
        $pupil = ProjectWeekPupil::where('id', $id)->first();
        $projectId = $pupil['projectID'];
        $pupil->delete();

        if (!ProjectWeekPupil::where('projectID', $projectId)->exists()){
            $project = ProjectWeek::where('id', $projectId)->first();
            $project->delete();
        }

        return response("Der Schüler wurde erfolgreich gelöscht", 200)
            ->header("Content-Type", "text/plain");
    }

    public function projectStatistics() {
        if (!Auth::user()->isTeacher() && !Auth::user()->isSupervisor())
            return response("Nicht berechtigt.", 403)
                ->header("Content-Type", "text/plain");
        else {
            $return = [];
            $projects = [];
            if (Auth::user()->isTeacher()) {
                $username = Auth::user()->username;
                $projects = ProjectWeek::where('teacher', $username)->get();
            } elseif (Auth::user()->isSupervisor()) {
                $projects = ProjectWeek::get();
            }
            foreach ($projects as $project) {
                $pupils = ProjectWeekPupil::where('projectID', $project->id)->get();
                $pupilData = [];
                foreach ($pupils as $pupil) {
                    $checked = "nicht bestätigt";
                    if ($pupil->checked) {
                        $checked = "bestätigt";
                    }
                    $pupilData[] = [
                        'name' => $pupil->name,
                        'class' => $pupil->class,
                        'checked' => $checked
                    ];
                }
                $membersCount = count($pupils) . "/" . $project->members;
                $teacher = User::where('username', $project->teacher)->get();
                if (count($teacher) >= 1) {
                    $teachername = substr($teacher[0]->firstname, 0, 1) . '. ' . $teacher[0]->lastname;
                } else {
                    $teachername = $project->teacher;
                }
                $return[] = [
                    'title' => $project->title,
                    'subject' => $project->subject,
                    'teacher' => $teachername,
                    'room' => $project->room,
                    'description' => $project->description,
                    'memberCount' => $membersCount,
                    'members' => $pupilData,
                    'material' => $project->material,
                ];
            }
            return response()
                ->json($return, 200)
                ->header("Content-Type", "text/plain");
        }
    }
}
