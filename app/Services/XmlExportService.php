<?php

namespace App\Services;

use SimpleXMLElement;

class XmlExportService
{
    public function reportToXml(array $report): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><academic_report/>');
        $xml->addAttribute('type', $report['type']);
        $this->add($xml, 'title', $report['title']);
        $this->add($xml, 'generated_at', now()->toIso8601String());

        match ($report['type']) {
            'course' => $this->appendCourseReport($xml, $report),
            'teacher' => $this->appendTeacherReport($xml, $report),
            'student' => $this->appendStudentReport($xml, $report),
            default => null,
        };

        return $xml->asXML() ?: '';
    }

    private function appendCourseReport(SimpleXMLElement $xml, array $report): void
    {
        $course = $xml->addChild('course');
        $course->addAttribute('id', (string) $report['course']->id);
        $this->add($course, 'name', $report['course']->name);

        $rows = $xml->addChild('students');
        foreach ($report['rows'] as $row) {
            $student = $rows->addChild('student');
            $student->addAttribute('id', (string) $row['student']->id);
            $this->add($student, 'name', $row['student']->name);
            $this->add($student, 'average', (string) $row['average']);
        }
    }

    private function appendTeacherReport(SimpleXMLElement $xml, array $report): void
    {
        $teacher = $xml->addChild('teacher');
        $teacher->addAttribute('id', (string) $report['teacher']->id);
        $this->add($teacher, 'name', $report['teacher']->name);

        $subjects = $xml->addChild('subjects');
        foreach ($report['rows'] as $row) {
            $subject = $subjects->addChild('subject');
            $subject->addAttribute('id', (string) $row['subject']->id);
            $this->add($subject, 'name', $row['subject']->name);
            $this->add($subject, 'average', (string) $row['average']);
            $this->add($subject, 'student_count', (string) $row['studentCount']);
        }
    }

    private function appendStudentReport(SimpleXMLElement $xml, array $report): void
    {
        $student = $xml->addChild('student');
        $student->addAttribute('id', (string) $report['student']->id);
        $this->add($student, 'name', $report['student']->name);
        $this->add($student, 'course', $report['student']->course?->name ?? 'N/A');
        $this->add($student, 'average', (string) $report['average']);

        $scores = $xml->addChild('scores');
        foreach ($report['scores'] as $score) {
            $scoreNode = $scores->addChild('score');
            $scoreNode->addAttribute('id', (string) $score->id);
            $this->add($scoreNode, 'subject', $score->subject?->name ?? 'N/A');
            $this->add($scoreNode, 'course', $score->subject?->course?->name ?? 'N/A');
            $this->add($scoreNode, 'label', $score->label);
            $this->add($scoreNode, 'value', (string) $score->value);
        }

        $alerts = $xml->addChild('alerts');
        foreach ($report['alerts'] as $alert) {
            $alertNode = $alerts->addChild('alert');
            $alertNode->addAttribute('id', (string) $alert->id);
            $this->add($alertNode, 'title', $alert->title);
            $this->add($alertNode, 'severity', $alert->severity);
            $this->add($alertNode, 'resolved', $alert->resolved ? 'true' : 'false');
            $this->add($alertNode, 'message', $alert->message);
        }
    }

    private function add(SimpleXMLElement $xml, string $name, ?string $value): void
    {
        $xml->addChild($name, htmlspecialchars($value ?? '', ENT_XML1 | ENT_COMPAT, 'UTF-8'));
    }
}
